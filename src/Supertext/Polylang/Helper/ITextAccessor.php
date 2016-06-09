<?php

namespace Supertext\Polylang\Helper;


interface ITextAccessor
{
    public function getPluginName();

    public function getTexts($post);

    public function setTexts($post, $texts);

    public function prepareTranslationPost($postId, $translationPostId);
}