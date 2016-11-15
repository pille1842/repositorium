<?php
namespace Repositorium;

class Markdown extends \Parsedown implements \Mni\FrontYAML\Markdown\MarkdownParser
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Insert an inline link
     *
     * Override Parsedown's inlineLink method to insert Repositorium links of the form [Document]().
     * If the excerpt does not fit this pattern, let Parsedown handle the link.
     *
     * @param  array $Excerpt  the excerpt we're dealing with
     * @return array           the element, either made by this or the parent method
     */
    protected function inlineLink($Excerpt)
    {
        $Element = array(
            'name' => 'a',
            'handler' => 'line',
            'text' => 'null',
            'attributes' => array(
                'href' => null,
                'title' => null
            )
        );

        $extent = 0;
        $remainder = $Excerpt['text'];

        if (preg_match('/\[((?:[^][]|(?R))*)\]\(&(.*?)\)/', $remainder, $matches)) {
            $config = $this->container->get('settings');
            if ($matches[2] == '') {
                $document = $matches[1];
            } else {
                $document = $matches[2];
            }
            $document = trim($document, DIRECTORY_SEPARATOR);
            $caption = $matches[1];
            $arrPath = $this->container->get('helpers')->documentNameToPathArray($document, $config['documentPathDelimiter']);
            $documentShort = $arrPath[count($arrPath) - 1];
            $path = trim(implode(DIRECTORY_SEPARATOR, $arrPath), DIRECTORY_SEPARATOR);
            $filebackend = $this->container->get('files');
            $router = $this->container->get('router');
            if (!$filebackend->fileExists($path)) {
                $Element['attributes']['class'] = 'redlink';
                $Element['attributes']['href'] = $router->pathFor('edit', ['document' => $document]);
                $Element['attributes']['title'] = 'Document does not exist (click to create it)';
            } else {
                $Element['attributes']['href'] = $router->pathFor('view', ['document' => $document]);
                if ($caption != $document) {
                    $Element['attributes']['title'] = $document;
                }
            }
            $extent .= strlen($matches[0]);
            $Element['text'] = $caption;
            $Element['isInternalReposLink'] = true;
            return array(
                'extent' => $extent,
                'element' => $Element
            );
        } else {
            return parent::inlineLink($Excerpt);
        }
    }

    protected function inlineImage($Excerpt)
    {
        if ( ! isset($Excerpt['text'][1]) or $Excerpt['text'][1] !== '[')
        {
            return;
        }

        $Excerpt['text']= substr($Excerpt['text'], 1);

        $Link = $this->inlineLink($Excerpt);

        if ($Link === null)
        {
            return;
        }

        if (isset($Link['element']['isInternalReposLink']) && $Link['element']['isInternalReposLink'] === true) {
            $Link['element']['attributes']['href'] .= '?raw';
        }

        $Inline = array(
            'extent' => $Link['extent'] + 1,
            'element' => array(
                'name' => 'img',
                'attributes' => array(
                    'src' => $Link['element']['attributes']['href'],
                    'alt' => $Link['element']['text'],
                    'class' => 'img-responsive'
                ),
            ),
        );

        $Inline['element']['attributes'] += $Link['element']['attributes'];

        unset($Inline['element']['attributes']['href']);

        return $Inline;
    }
}