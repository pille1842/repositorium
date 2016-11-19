<?php
/**
 * Configure your Repositorium here.
 */

return array(
    /**
     * Whether or not to display detailed error messages ("debug mode"). You should disable this
     * in production environments.
     */
    'displayErrorDetails' => true,
    /**
     * Whether or not to add a "Content-Length" header to all outgoing responses.
     */
    'addContentLengthHeader' => true,
    /**
     * The path to the storage repository as seen from public/index.php.
     */
    'pathToRepository' => '../storage',
    /**
     * The locale to use. Ensure that the locale you give here is installed on your system and
     * available to PHP.
     */
    'locale' => 'en_US.UTF-8',
    /**
     * The default content language. This can be overriden per file with the "language" key in
     * the YAML frontmatter.
     */
    'language' => 'en',
    /**
     * The delimiter between path segments. You should probably leave it be.
     */
    'documentPathDelimiter' => '/',
    /**
     * The regular expression to match paths in URIs. Better leave this default.
     */
    'documentPathMatch' => '.*',
    /**
     * The default extension for new documents. Using ".md" will ensure that all newly created
     * files will be parsed by Markdown.
     */
    'documentExtension' => '.md',
    /**
     * The full path and possibly options to Git on your system. Make sure PHP can execute this file.
     */
    'gitPath' => '/usr/bin/git',
    /**
     * The full path and possibly options to Ack on your system. Make sure PHP can execute this file.
     */
    'ackPath' => '/usr/bin/ack -i',
    /**
     * The URL to display in the sidebar of the edit form. This should contain instructions for using
     * the Markdown syntax.
     */
    'syntaxHelpUrl' => 'http://daringfireball.net/projects/markdown/syntax',
    /**
     * The URL to display in the sidebar of the edit form. This should contain instructions for using
     * YAML in the document frontmatter.
     */
    'yamlHelpUrl' => 'http://www.yaml.org/'
);
