<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;

class PlgContentDigivideobox extends CMSPlugin
{
    public function onContentPrepare($context, &$article, &$params, $page = 0)
    {
        $pattern = '/{videobox}(.*?){\/videobox}/i';
        $article->text = preg_replace_callback($pattern, function($matches) {
            $videoId = $matches[1];
            $opacity = $this->params->get('opacity', 0.9);

            // Inietta JS solo una volta per pagina
            static $jsInjected = false;
            if (!$jsInjected) {
                $this->injectModalScript();
                $jsInjected = true;
            }

            return $this->generateEmbedCode($videoId, $opacity);
        }, $article->text);
    }

    private function injectModalScript()
    {
        $doc = Factory::getDocument();
        // Questa funzione JS viene definita una sola volta
        $doc->addScriptDeclaration(<<<JS
            function digivideoboxCloseModal(videoId) {
                var modal = document.getElementById('yt-modal-' + videoId);
                var iframe = document.getElementById('yt-iframe-' + videoId);
                if (modal) modal.style.display = "none";
                if (iframe) {
                    var src = iframe.src;
                    iframe.src = '';
                    iframe.src = src;
                }
            }
JS
        );
    }

    private function generateEmbedCode($videoId, $opacity)
    {
        $doc = Factory::getDocument();
        $doc->addStyleDeclaration("
            .digi-videobox-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,{$opacity});
                z-index: 9999;
            }
            .digi-videobox-iframe {
                max-width: unset !important;
                width: 80% !important;
                height: 80%;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
        ");

        return '
        <div class="digi-videobox-thumb-container" style="display:inline-block;position:relative;cursor:pointer;max-width:100%;" onclick="document.getElementById(\'yt-modal-' . $videoId . '\').style.display=\'block\'">
        	<img class="digi-videobox" src="https://img.youtube.com/vi/' . $videoId . '/0.jpg" style="display:block;max-width:100%;">
        	<span class="digi-videobox-play-btn"></span>
        </div>
        <div id="yt-modal-' . $videoId . '" class="digi-videobox-modal">
        	<iframe id="yt-iframe-' . $videoId . '" class="digi-videobox-iframe" src="https://www.youtube.com/embed/' . $videoId . '?autoplay=1" frameborder="0" allowfullscreen>
        	</iframe>
        	<button style="position:absolute;top:20px;right:20px;z-index:10000" onclick="digivideoboxCloseModal(\'' . $videoId . '\')">
        		×
        	</button>
        </div>
        ';
    }
}
