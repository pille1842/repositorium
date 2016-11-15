<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$config = include('../configuration.php');

setlocale(LC_ALL, $config['locale']);

session_start();

$app = new \Slim\App(['settings' => $config]);
$container = $app->getContainer();

$container['logger'] = function ($c) {
    $logger = new \Monolog\Logger('repositorium_logger');
    $fileHandler = new \Monolog\Handler\StreamHandler('../logs/repositorium.log');
    $logger->pushHandler($fileHandler);
    return $logger;
};

$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig('../templates', [
        'cache' => '../cache/templates_c'
    ]);

    $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new \Slim\Views\TwigExtension($c['router'], $basePath));

    return $view;
};

$container['parser'] = function ($c) {
    return new \Mni\FrontYAML\Parser(null, new \Repositorium\Markdown($c));
};

$container['files'] = function ($c) {
    return new \Repositorium\GitFileBackend($c);
};

$container['helpers'] = function ($c) {
    return new \Repositorium\Helpers;
};

$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};

/**
 * ROUTE: /api/v1/exists (GET)
 * ---------------------------
 *
 * Checks if a file exists in the repository.
 *
 * Parameters:
 *   - file    the name of the file
 *
 * Returns:
 *   - JSON { status: OK/NOK, [error: Message], [exists: true/false] }
 */
$app->get('/api/v1/exists', function (Request $request, Response $response) {
    $file = $request->getParam('file');
    if ($file === null) {
        $data = array(
            'status' => 'NOK',
            'error' => 'Parameter file is required.'
        );
        $status = 400;
    } else {
        $filebackend = $this->get('files');
        $data = array(
            'status' => 'OK',
            'exists' => $filebackend->fileExists($file)
        );
        $status = 200;
    }

    return $response->withStatus($status)->withJson($data);
})->setName('api-exists');

/**
 * ROUTE: /api/v1/title (GET)
 * --------------------------
 *
 * Generates a valid filename from a title.
 *
 * Parameters:
 *   - title     The title
 *   - document  Path to the document (for including the parent directory)
 *
 * Returns:
 *   - JSON { status: OK/NOK, [error: Message], [filename: The-generated-filename] }
 */
$app->get('/api/v1/title', function (Request $request, Response $response) {
    $title = $request->getParam('title');
    $document = $request->getParam('document');
    $filebackend = $this->get('files');

    if ($title !== null) {
        $data = array(
            'status' => 'OK',
            'filename' => $this->get('helpers')->titleToDocumentName($title, $this->get('settings')['documentExtension'])
        );
        if ($document !== null) {
            $arrPath = $this->get('helpers')->documentNameToPathArray($document, $this->get('settings')['documentPathDelimiter']);
            if (!$filebackend->isDirectory($document)) {
                array_pop($arrPath);
            }
            $documentPath = trim(implode(DIRECTORY_SEPARATOR, $arrPath), DIRECTORY_SEPARATOR);
            if ($documentPath != '') {
                $data['filename'] = $documentPath . DIRECTORY_SEPARATOR . $data['filename'];
            }
        }
        $found = false;
        $count = 0;
        do {
            $found = $filebackend->fileExists($data['filename']);
            if ($found) {
                $count++;
                $tmpTitle = $title.' '.$count;
                $data['filename'] = $documentPath.DIRECTORY_SEPARATOR.$this->get('helpers')->titleToDocumentName($tmpTitle, $this->get('settings')['documentExtension']);
            }
        } while ($found);
        $status = 200;
    } else {
        $data = array(
            'status' => 'NOK',
            'error' => 'Parameter title is required.'
        );
        $status = 400;
    }

    return $response->withStatus($status)->withJson($data);
})->setName('api-title');

/**
 * ROUTE: /{document}/history (GET)
 * --------------------------------
 *
 * Returns a view with all the versions of a file.
 */
$app->get('/{document:'.$config['documentPathMatch'].'}/history', function (Request $request, Response $response) {
    $messages = $this->get('flash')->getMessages();

    $document = $request->getAttribute('document');
    $config = $this->get('settings');
    $arrPath = $this->get('helpers')->documentNameToPathArray($document, $config['documentPathDelimiter']);
    $documentShort = $arrPath[count($arrPath) - 1];
    $path = implode(DIRECTORY_SEPARATOR, $arrPath);
    $filebackend = $this->get('files');

    $history = $filebackend->getFileHistory($path)->getFullHistory();

    $sidebarContent = '<p>Click on a version to see its contents. Use the radio boxes to compare two versions. '.
                      'As a general rule, you should place the left radio button above the right one.</p>'.
                      '<p><a href="'.$this->router->pathFor('view', ['document' => $document]).'" class="btn'.
                      ' btn-default">Back to the document</a></p>';
    $sidebarFrontmatter = array('title' => 'History of '.$document);

    return $this->view->render($response, 'history.html', [
        'document' => $document,
        'frontmatter' => array('title' => "History of $documentShort"),
        'sidebar' => $sidebarContent,
        'sidebarFrontmatter' => $sidebarFrontmatter,
        'history' => $history,
        'messages' => $messages
    ]);
})->setName('history');

/**
 * ROUTE: /{document}/version/{commit} (GET)
 * -----------------------------------------
 *
 * Returns a view with a specific version of a file.
 *
 * Parameters:
 *   - download  If set, file will be presented to the browser as an attachment for downloading.
 *   - raw       If set, file will be given to the browser in plain text instead of HTML.
 *   - remark    If set, file will be turned into a Remark.js slideshow.
 */
$app->get('/{document:'.$config['documentPathMatch'].'}/version/{commit}', function (Request $request, Response $response) {
    $messages = $this->get('flash')->getMessages();

    $config = $this->get('settings');
    $filebackend = $this->get('files');
    $document = trim($request->getAttribute('document'), DIRECTORY_SEPARATOR);
    $version = $request->getAttribute('commit');
    $arrPath = $this->get('helpers')->documentNameToPathArray($document, $config['documentPathDelimiter']);
    $documentShort = $arrPath[count($arrPath) - 1];
    $path = trim(implode(DIRECTORY_SEPARATOR, $arrPath), DIRECTORY_SEPARATOR);

    $content = $filebackend->getFileVersion($path, $version);
    if ($content === false) {
        $this->get('flash')->addWarning("Unable to fetch version $version of $documentShort. Sorry.");

        return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('view', ['document' => '']));
    }

    if ($request->getQueryParam('download') === null) {
        if (!$filebackend->versionIsBinary($path, $version)) {
            if ($request->getQueryParam('raw') !== null) {
                return $response->withHeader('Content-Type', 'text/plain')
                                ->withBody($filebackend->getVersionStreamInterface($path, $version));
            }
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $language = $this->get('helpers')->getFileLanguage($ext);
            if ($language !== false) {
                if ($language == 'markdown') {
                    $content = $filebackend->getFileVersion($path, $version);
                } else {
                    $content = "---\ntitle: $documentShort\n---\n```$language\n";
                    $content .= $filebackend->getFileVersion($path, $version);
                    $content .= "\n```\n";
                }
            } else {
                $content = "---\ntitle: $documentShort\n---\n```\n";
                $content .= $filebackend->getFileVersion($path, $version);
                $content .= "\n```\n";
            }
        } else {
            if ($request->getQueryParam('raw') !== null) {
                return $response->withHeader('Content-Type', $filebackend->getFileVersionMimetype($path, $version))
                                ->withBody($filebackend->getVersionStreamInterface($path, $version));
            } else {
                if (substr($filebackend->getFileVersionMimetype($path, $version), 0, 5) == 'image') {
                    $content = "---\ntitle: $documentShort\n---\n\n".
                               "![$documentShort](&$path)\n\n";
                } else {
                    return $response->withHeader('Content-Type', $filebackend->getFileVersionMimetype($path, $version))
                                    ->withBody($filebackend->getVersionStreamInterface($path, $version));
                }
            }
        }
    } else {
        return $response->withHeader('Content-Disposition', "attachment;filename=$documentShort")
                        ->withHeader('Content-Type', $filebackend->getFileVersionMimetype($path, $version))
                        ->withBody($filebackend->getVersionStreamInterface($path, $version));
    }

    $mtime = $filebackend->getVersionMtime($path, $version);

    $parser = $this->get('parser');
    if ($request->getQueryParam('remark') === null) {
        try {
            $parserResult = $parser->parse($content);
            $documentContent = $parserResult->getContent();
            $documentFrontmatter = $parserResult->getYAML();
        } catch (Exception $e) {
            $documentContent = "<h1>Oops! Something went wrong.</h1>\n\n<p>".
                               "Repositorium was unable to parse this document due to ".
                               "the following reason:</p>\n\n".
                               "<p>".$e->getMessage()."</p>";
            $documentFrontmatter = null;
        }
    } else {
        $parserResult = $parser->parse($content, false);
        try {
            $documentContent = $parserResult->getContent();
            $documentFrontmatter = $parserResult->getYAML();
            if ($documentFrontmatter === null || empty($documentFrontmatter)) {
                $documentFrontmatter = array('title' => $documentShort);
            }
        } catch (Exception $e) {
            $documentContent = "# Oops! Something went wrong.\n\n".
                               "Repositorium was unable to parse this document due to ".
                               "the following reason:\n\n".
                               $e->getMessage();
            $documentFrontmatter = array('title' => $documentShort);
        }

        return $this->view->render($response, 'remark.html', [
            'frontmatter' => $documentFrontmatter,
            'content' => $documentContent
        ]);
    }

    $defaultFrontmatter = array('title' => $documentShort, 'language' => $config['language']);
    if ($documentFrontmatter !== null && !empty($documentFrontmatter)) {
        $finalFrontmatter = array_merge($defaultFrontmatter, $documentFrontmatter);
    } else {
        $finalFrontmatter = $defaultFrontmatter;
    }

    $sidebarFrontmatter = array('title' => "Version $version of $documentShort");
    $sidebarContent = "<p>This version of $documentShort was created at ".
                      date('F jS, Y \\a\\t g:ia', $mtime).
                    '</p><p><form method="post" action="'.$this->router->pathFor('restore', ['document' => $document, 'commit' => $version]).'">'.
                    '<button type="submit" class="btn btn-default btn-block">Restore this version</button></form></p>';

    $arrBreadcrumbs = array();
    foreach ($arrPath as $key => $value) {
        $caption = $value;
        $path = '';
        for ($i = 0; $i < $key; $i++) {
            $path .= $arrPath[$i] . $config['documentPathDelimiter'];
        }
        $path .= $value;
        $arrBreadcrumbs[] = array('caption' => $caption, 'path' => $path);
    }

    return $this->view->render($response, 'version.html', [
        'document' => $document,
        'version' => $version,
        'frontmatter' => $finalFrontmatter,
        'breadcrumbs' => $arrBreadcrumbs,
        'content' => $documentContent,
        'sidebar' => $sidebarContent,
        'sidebarFrontmatter' => $sidebarFrontmatter,
        'mtime' => $mtime,
        'language' => $language,
        'messages' => $messages
    ]);
})->setName('version');

/**
 * ROUTE: /{document}/compare/{range} (GET)
 * ----------------------------------------
 *
 * Compare two or more versions of a file based on a version range
 * and return a view with the resulting diff.
 */
$app->get('/{document:'.$config['documentPathMatch'].'}/compare/{range}', function (Request $request, Response $response) {
    $messages = $this->get('flash')->getMessages();

    $config = $this->get('settings');
    $filebackend = $this->get('files');
    $document = trim($request->getAttribute('document'), DIRECTORY_SEPARATOR);
    $range = $request->getAttribute('range');
    $version = $request->getAttribute('commit');
    $arrPath = $this->get('helpers')->documentNameToPathArray($document, $config['documentPathDelimiter']);
    $documentShort = $arrPath[count($arrPath) - 1];
    $path = trim(implode(DIRECTORY_SEPARATOR, $arrPath), DIRECTORY_SEPARATOR);

    $diff = $filebackend->getFileDiff($path, $range);
    if ($diff === false) {
        $this->get('flash')->addMessage('error', "Unable to generate diff. See the logs for more information.");
        return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('view', ['document' => $document]));
    }

    $arrDiff = explode("\n", $diff);
    $numberAdditions = 0;
    $numberDeletions = 0;
    foreach ($arrDiff as $line) {
        if (substr($line, 0, 1) == '+') {
            $numberAdditions += 1;
        } elseif (substr($line, 0, 1) == '-') {
            $numberDeletions += 1;
        }
    }
    $numberAdditions -= 1;
    $numberDeletions -= 1;
    $totalNumber = $numberAdditions + $numberDeletions;
    if ($totalNumber > 0) {
        $shareAdditions = ($numberAdditions / $totalNumber) * 100;
        $shareDeletions = ($numberDeletions / $totalNumber) * 100;
    } else {
        $shareAdditions = 50;
        $shareDeletions = 50;
    }

    $frontmatter = array('title' => "$documentShort: Compare $range");
    $content = "<h1>$documentShort: Compare <tt>$range</tt></h1>\n".
               '<pre><code class="language-git">'."\n".htmlspecialchars($diff)."\n".'</code></pre>';
    $sidebarFrontmatter = array('title' => 'Comparison statistics');

    return $this->view->render($response, 'compare.html', [
        'document' => $document,
        'range' => $range,
        'frontmatter' => $frontmatter,
        'sidebarFrontmatter' => $sidebarFrontmatter,
        'content' => $content,
        'numberadditions' => $numberAdditions,
        'numberdeletions' => $numberDeletions,
        'shareadditions' => $shareAdditions,
        'sharedeletions' => $shareDeletions,
        'messages' => $messages
    ]);
})->setName('compare');

/**
 * ROUTE: /{document} (PUT)
 * ------------------------
 *
 * Create a new file or store a new version of an existing file. Redirects
 * to the "view view" of the file.
 *
 * Parameters:
 *   - content    The new content of the file.
 *   - commitmsg  A message to be stored with the commit.
 */
$app->put('/{document:'.$config['documentPathMatch'].'}', function (Request $request, Response $response) {
    $messages = $this->get('flash')->getMessages();

    $document = $request->getAttribute('document');
    $config = $this->get('settings');
    $arrPath = $this->get('helpers')->documentNameToPathArray($document, $config['documentPathDelimiter']);
    $documentShort = $arrPath[count($arrPath) - 1];
    $path = implode(DIRECTORY_SEPARATOR, $arrPath);
    $filebackend = $this->get('files');
    $content = $request->getParsedBodyParam('content');
    $commitmsg = $request->getParsedBodyParam('commitmsg');

    $status = $filebackend->storeFile($path, $content, $commitmsg);
    if (!$status) {
        $this->get('flash')->addMessage('error', "Unable to store file. See the logs for more information.");
    }
    return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('view', ['document' => $document]));
})->setName('update');

/**
 * ROUTE: /{document} (POST)
 * -------------------------
 *
 * Rename a file. Returns the "view view" of the resulting new file.
 *
 * Parameters:
 *   - target  New name of the file.
 */
$app->post('/{document:'.$config['documentPathMatch'].'}', function (Request $request, Response $response) {
    $document = $request->getAttribute('document');
    $config = $this->get('settings');
    $arrPath = $this->get('helpers')->documentNameToPathArray($document, $config['documentPathDelimiter']);
    $documentShort = $arrPath[count($arrPath) - 1];
    $path = implode(DIRECTORY_SEPARATOR, $arrPath);
    $target = $request->getParam('target');
    $arrTarget = $this->get('helpers')->documentNameToPathArray($target, $config['documentPathDelimiter']);
    $targetShort = $arrTarget[count($arrTarget) - 1];
    $targetPath = implode(DIRECTORY_SEPARATOR, $arrTarget);
    $filebackend = $this->get('files');

    if ($filebackend->fileExists($path)) {
        $success = $filebackend->moveFile($path, $target);
        if (!$success) {
            $this->get('flash')->addMessage('warning', "Unable to move $documentShort to $targetPath. Maybe the target already exists?");
            return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('view', ['document' => $path]));
        } else {
            $this->get('flash')->addMessage('success', "$documentShort was renamed to $targetPath.");
            return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('view', ['document' => $targetPath]));
        }
    } else {
        $this->get('flash')->addMessage('error', "Unable to move $path: File does not exist.");
        return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('view', ['document' => '']));
    }
})->setName('rename');

/**
 * ROUTE: /{document}/restore/{commit} (POST)
 * ------------------------------------------
 *
 * Restore a specific version of the file to the effect that that version of the file
 * is the new current version. Returns the "view view" of the file.
 */
$app->post('/{document:'.$config['documentPathMatch'].'}/restore/{commit}', function (Request $request, Response $response) {
    $document = $request->getAttribute('document');
    $commit = $request->getAttribute('commit');
    $config = $this->get('settings');
    $arrPath = $this->get('helpers')->documentNameToPathArray($document, $config['documentPathDelimiter']);
    $documentShort = $arrPath[count($arrPath) - 1];
    $path = implode(DIRECTORY_SEPARATOR, $arrPath);
    $filebackend = $this->get('files');

    $success = $filebackend->restoreFileVersion($path, $commit);
    if ($success) {
        $this->get('flash')->addMessage('success', "Version $commit of $document was restored and is now the current version.");
        return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('view', ['document' => $document]));
    } else {
        $this->get('flash')->addMessage('error', "Version $commit of $document could not be restored. See the logs for more information.");
        return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('view', ['document' => '']));
    }
})->setName('restore');

/**
 * ROUTE: /{document} (DELETE)
 * ---------------------------
 *
 * Delete the given file. Returns a "view view" of the parent directory.
 */
$app->delete('/{document:'.$config['documentPathMatch'].'}', function (Request $request, Response $response) {
    $document = $request->getAttribute('document');
    $config = $this->get('settings');
    $arrPath = $this->get('helpers')->documentNameToPathArray($document, $config['documentPathDelimiter']);
    $documentShort = $arrPath[count($arrPath) - 1];
    $path = implode(DIRECTORY_SEPARATOR, $arrPath);
    $arrParent = $arrPath;
    array_pop($arrParent);
    $parent = implode(DIRECTORY_SEPARATOR, $arrParent);
    $filebackend = $this->get('files');

    if ($filebackend->fileExists($path)) {
        $success = $filebackend->deleteFile($path);
        if (!$success) {
            $this->get('flash')->addMessage('warning', "Unable to delete $documentShort. Please check permissions.");
        } else {
            $this->get('flash')->addMessage('success', "$documentShort was deleted.");
        }

        return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('view', ['document' => $parent]));
    } else {
        $this->get('flash')->addMessage('warning', "Unable to delete $documentShort: No such file or directory.");

        return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('view', ['document' => $parent]));
    }
})->setName('destroy');

/**
 * ROUTE: /{document}/edit (GET)
 * -----------------------------
 *
 * Returns a view with an editor form for the given file.
 */
$app->get('/{document:'.$config['documentPathMatch'].'}/edit', function (Request $request, Response $response) {
    $messages = $this->get('flash')->getMessages();

    $document = $request->getAttribute('document');
    $config = $this->get('settings');
    $arrPath = $this->get('helpers')->documentNameToPathArray($document, $config['documentPathDelimiter']);
    $documentShort = $arrPath[count($arrPath) - 1];
    $path = implode(DIRECTORY_SEPARATOR, $arrPath);
    $filebackend = $this->get('files');

    if ($filebackend->fileExists($path)) {
        $content = $filebackend->getFileContent($path);
        if ($filebackend->isBinary($path)) {
            $this->get('flash')->addMessage("error",
                                            "$documentShort is a binary file and cannot be edited online.");
            return $response->withStatus(302)
                            ->withHeader('Location',
                                         $this->get('router')->pathFor('view', ['document' => '']));
        }
        $documentIsNew = false;
    } else {
        if (($title = $request->getParam('title')) !== null) {
            $content = "# $title\n\n";
        } else {
            $content = "# $documentShort\n\n";
        }
        $documentIsNew = true;
    }

    $modeParam = $request->getParam('mode');
    if ($modeParam === null) {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $language = $this->get('helpers')->getFileLanguage($ext);
    } else {
        $language = $modeParam;
    }
    $editorlanguage = $this->get('helpers')->getEditorLanguage($language);
    $editorDependencies = $this->get('helpers')->getEditorModeDependencies($editorlanguage);
    $editorAddons = $this->get('helpers')->getEditorAddonDependencies($editorDependencies);
    $editorCss = $this->get('helpers')->getEditorAddonCss($editorAddons);
    $mimetype = $filebackend->getFileMimetype($path);

    $frontmatter = array('title' => "Edit $documentShort", 'language' => $config['language']);

    $sidebar = '';
    if ($language == 'markdown') {
        $sidebar .= "<p>You can use <a href=\"".$config['syntaxHelpUrl']."\" target=\"blank\">Markdown</a> ".
                    "to format the contents of this document.</p>\n\n".
                    "<p>To set a link to another document in this wiki, simply write</p>\n\n".
                    "<pre>[the".$config['documentPathDelimiter']."path.md]()</pre> ".
                    "<p>or use a</p>\n\n<pre>[caption](&the".$config['documentPathDelimiter']."path.md)</pre>\n\n".
                    "(notice the ampersand symbol).</p>\n\n".
                    "<p>At the beginning of the document you can place a <a href=\"".$config['yamlHelpUrl'].
                    "\" target=\"_blank\">YAML</a> block, the so called <em>frontmatter</em>, to give ".
                    "certain meta information about this document. A simple example:</p>\n\n".
                    "<pre>---\ntitle: This Document's Title\ndescription: A sentence to describe the content\n---".
                    "</pre>";
    } else {
        $sidebar .= "<p>The language of this document was detected as \"<tt>$language</tt>\". ".
                    "You can force another mode by choosing one here:</p>\n".
                    "<form method=\"get\" action=\"".$this->router->pathFor('edit', ['document' => $path]).
                    "\" class=\"form\">\n<div class=\"form-group\"><select name=\"mode\" class=\"form-control\">\n";
        $languageList = $this->get('helpers')->getLanguageList();
        foreach ($languageList as $item => $extensions) {
            $selected = ($language == $item ? ' selected' : '');
            $sidebar .= "<option value=\"$item\"$selected>$item</option>\n";
        }
        $sidebar .= "</select></div>\n<button type=\"submit\" class=\"btn btn-default\">Apply</button>\n";
        $sidebar .= "</form>\n\n";
    }

    return $this->view->render($response, 'edit.html', [
        'document' => $document,
        'frontmatter' => $frontmatter,
        'content' => $content,
        'sidebar' => $sidebar,
        'language' => $language,
        'editorlanguage' => $editorlanguage,
        'editordeps' => $editorDependencies,
        'editoraddons' => $editorAddons,
        'editorcss' => $editorCss,
        'mimetype' => $mimetype,
        'documentisnew' => $documentIsNew,
        'messages' => $messages
    ]);
})->setName('edit');

/**
 * ROUTE: /{document}
 * ------------------
 *
 * Returns a view with the current version of a file.
 *
 * Parameters:
 *   - download  If set, file will be presented to the browser as an attachment for downloading.
 *   - raw       If set, file will be given to the browser in plain text instead of HTML.
 *   - remark    If set, file will be turned into a Remark.js slideshow.
 */
$app->get('/[{document:'.$config['documentPathMatch'].'}]', function (Request $request, Response $response) {
    $messages = $this->get('flash')->getMessages();

    $config = $this->get('settings');
    $filebackend = $this->get('files');
    $document = trim($request->getAttribute('document'), DIRECTORY_SEPARATOR);
    if ($document != '') {
        $arrPath = $this->get('helpers')->documentNameToPathArray($document, $config['documentPathDelimiter']);
        $documentShort = $arrPath[count($arrPath) - 1];
    } else {
        $arrPath = array();
        $documentShort = '/';
    }
    $path = trim(implode(DIRECTORY_SEPARATOR, $arrPath), DIRECTORY_SEPARATOR);
    $isDownloadable = true;
    $isEditable = true;
    $language = '';
    if ($filebackend->fileExists($path)) {
        if ($filebackend->isDirectory($path)) {
            // Display directory listing or index file
            $isDownloadable = false;
            $isEditable = false;
            $indexPath = trim($path . DIRECTORY_SEPARATOR . 'index' . $config['documentExtension'], DIRECTORY_SEPARATOR);
            if ($filebackend->fileExists($indexPath)) {
                return $response->withStatus(302)
                                ->withHeader('Location',
                                             $this->router->pathFor('view', ['document' => $indexPath]));
            } else {
                $files = $filebackend->getDirectoryFiles($path);
                $content = "---\ntitle: Index of $path\n---\n# Directory index of $path\n";
                if (!empty($files)) {
                    foreach ($files as $file) {
                        if (substr($file, -1, 1) == '/') {
                            $isDir = true;
                        } else {
                            $isDir = false;
                        }
                        $fDocument = basename($file);
                        if ($isDir) {
                            $fDocument .= '/';
                        }
                        $fPath = trim($document.$config['documentPathDelimiter'].$fDocument,
                                      $config['documentPathDelimiter']);
                        if ($isDir) {
                            $content .= "* **[$fDocument](&$fPath)**\n";
                        } else {
                            $content .= "* [$fDocument](&$fPath)\n";
                        }
                    }
                }
                $content .= "\n\nYou can display a custom directory index here by placing a file called ".
                            "`index".$config['documentExtension']."` in this folder. ".
                            "[Click here](&".trim($indexPath,$config['documentPathDelimiter']).") ".
                            "to do that now.";
            }
        } else {
            if ($request->getQueryParam('download') === null) {
                if (!$filebackend->isBinary($path)) {
                    if ($request->getQueryParam('raw') !== null) {
                        return $response->withHeader('Content-Type', 'text/plain')
                                        ->withBody($filebackend->getStreamInterface($path));
                    }
                    $ext = pathinfo($path, PATHINFO_EXTENSION);
                    $language = $this->get('helpers')->getFileLanguage($ext);
                    if ($language !== false) {
                        if ($language == 'markdown') {
                            $content = $filebackend->getFileContent($path);
                        } else {
                            $content = "---\ntitle: $documentShort\n---\n```$language\n";
                            $content .= $filebackend->getFileContent($path);
                            $content .= "\n```\n";
                        }
                    } else {
                        $content = "---\ntitle: $documentShort\n---\n```\n";
                        $content .= $filebackend->getFileContent($path);
                        $content .= "\n```\n";
                    }
                } else {
                    if ($request->getQueryParam('raw') !== null) {
                        return $response->withHeader('Content-Type', $filebackend->getFileMimetype($path))
                                        ->withBody($filebackend->getStreamInterface($path));
                    } else {
                        if (substr($filebackend->getFileMimetype($path), 0, 5) == 'image') {
                            $content = "---\ntitle: $documentShort\n---\n\n".
                                       "![$documentShort](&$path)\n\n";
                        } else {
                            return $response->withHeader('Content-Type', $filebackend->getFileMimetype($path))
                                            ->withBody($filebackend->getStreamInterface($path));
                        }
                    }
                }
            } else {
                return $response->withHeader('Content-Disposition', "attachment;filename=$documentShort")
                                ->withHeader('Content-Type', $filebackend->getFileMimetype($path))
                                ->withBody($filebackend->getStreamInterface($path));
            }
        }
        $mtime = $filebackend->getFileMtime($path);
    } else {
        $content = "# $documentShort does not exist.\n\n".
                   "You can create it now by [clicking here]".
                   "(".$this->router->pathFor('edit', ['document' => $path]).").\n";
        $mtime = time();
    }

    $parser = $this->get('parser');
    if ($request->getQueryParam('remark') === null) {
        try {
            $parserResult = $parser->parse($content);
            $documentContent = $parserResult->getContent();
            $documentFrontmatter = $parserResult->getYAML();
        } catch (Exception $e) {
            $documentContent = "<h1>Oops! Something went wrong.</h1>\n\n<p>".
                               "Repositorium was unable to parse this document due to ".
                               "the following reason:</p>\n\n".
                               "<p>".$e->getMessage()."</p>";
            $documentFrontmatter = null;
        }
    } else {
        $parserResult = $parser->parse($content, false);
        try {
            $documentContent = $parserResult->getContent();
            $documentFrontmatter = $parserResult->getYAML();
            if ($documentFrontmatter === null || empty($documentFrontmatter)) {
                $documentFrontmatter = array('title' => $documentShort);
            }
        } catch (Exception $e) {
            $documentContent = "# Oops! Something went wrong.\n\n".
                               "Repositorium was unable to parse this document due to ".
                               "the following reason:\n\n".
                               $e->getMessage();
            $documentFrontmatter = array('title' => $documentShort);
        }

        return $this->view->render($response, 'remark.html', [
            'frontmatter' => $documentFrontmatter,
            'content' => $documentContent
        ]);
    }


    $defaultFrontmatter = array('title' => $documentShort, 'language' => $config['language']);
    if ($documentFrontmatter !== null && !empty($documentFrontmatter)) {
        $finalFrontmatter = array_merge($defaultFrontmatter, $documentFrontmatter);
    } else {
        $finalFrontmatter = $defaultFrontmatter;
    }

    $arrBreadcrumbs = array();
    foreach ($arrPath as $key => $value) {
        $caption = $value;
        $path = '';
        for ($i = 0; $i < $key; $i++) {
            $path .= $arrPath[$i] . $config['documentPathDelimiter'];
        }
        $path .= $value;
        $arrBreadcrumbs[] = array('caption' => $caption, 'path' => $path);
    }

    $content = null;
    if (isset($finalFrontmatter['sidebar']) && $finalFrontmatter['sidebar'] != '') {
        $sidebarDocument = $finalFrontmatter['sidebar'];
        $arrSidebarPath = $this->get('helpers')->documentNameToPathArray($sidebarDocument, $config['documentPathDelimiter']);
        $sidebarDocumentShort = $arrSidebarPath[count($arrSidebarPath) - 1];
        $sidebarPath = implode(DIRECTORY_SEPARATOR, $arrSidebarPath);

        if ($filebackend->fileExists($sidebarPath)) {
            if ($filebackend->isDirectory($sidebarPath)) {
                // Display directory listing or index file
                $indexSidebarPath = $sidebarPath . DIRECTORY_SEPARATOR . 'Index' . $config['documentExtension'];
                if ($filebackend->fileExists($indexSidebarPath)) {
                    $content = $filebackend->getFileContent($indexSidebarPath);
                } else {
                    $content = "---\ntitle: Uh-oh!\n---\n".
                               "Displaying directory indizes in the sidebar is currently not supported.";
                }
            } else {
                $content = $filebackend->getFileContent($sidebarPath);
            }
        } else {
            $content = "# $sidebarDocumentShort does not exist.";
        }
    } else {
        $arrSidebars = $arrPath;
        rsort($arrSidebars);
        $max = count($arrSidebars) - 1;
        foreach ($arrSidebars as $key => $value) {
            $sidebarPath = '';
            for ($i = $max; $i > $key; $i--) {
                $sidebarPath .= $arrSidebars[$i] . DIRECTORY_SEPARATOR;
            }
            $sidebarPath .= 'sidebar' . $config['documentExtension'];
            if ($filebackend->fileExists($sidebarPath)) {
                $content = $filebackend->getFileContent($sidebarPath);
                break;
            }
        }
    }
    if ($content === null) {
        $arrDir = $arrPath;
        if (!$filebackend->isDirectory($path)) {
            array_pop($arrDir);
        }
        $sidebarDir = implode($config['documentPathDelimiter'], $arrDir);
        $sidebarDir .= $config['documentPathDelimiter'].'sidebar.md';
        $sidebarDir = trim($sidebarDir, $config['documentPathDelimiter']);
        $content = "---\ntitle: Custom Sidebar\n---\n".
                   "You can display a custom sidebar here in the following ways:\n\n";
        if ($language == 'markdown') {
            $content .= "* Specify a `sidebar` document in the YAML frontmatter of this ".
                        "document. The sidebar will only be displayed for this document.\n";
        }
        $content .= "* Put a document called `sidebar".$config['documentExtension']."` in ".
                    "this document's directory. It will be displayed for all documents ".
                    "in this folder. [Click here](&$sidebarDir) to do that now.\n".
                    "* Put a `sidebar".$config['documentExtension']."` document in one of ".
                    "the parent directories. Repositorium will walk up the directory tree ".
                    "until it finds a sidebar to display.";
    }
    try {
        $sidebarParserResult = $parser->parse($content);
        $sidebarContent = $sidebarParserResult->getContent();
        $sidebarFrontmatter = $sidebarParserResult->getYAML();
    } catch (Exception $e) {
        $sidebarContent = "<h1>Oops! Something went wrong.</h1>\n\n<p>".
                           "Repositorium was unable to parse this document due to ".
                           "the following reason:</p>\n\n".
                           "<p>".$e->getMessage()."</p>";
        $sidebarFrontmatter = null;
    }

    return $this->view->render($response, 'view.html', [
        'document' => $document,
        'frontmatter' => $finalFrontmatter,
        'breadcrumbs' => $arrBreadcrumbs,
        'content' => $documentContent,
        'sidebar' => $sidebarContent,
        'sidebarFrontmatter' => $sidebarFrontmatter,
        'mtime' => $mtime,
        'isDownloadable' => $isDownloadable,
        'isEditable' => $isEditable,
        'language' => $language,
        'messages' => $messages
    ]);
})->setName('view');

// RUN THE APP
$app->run();
