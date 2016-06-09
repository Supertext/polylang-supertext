<?php

namespace Supertext\Polylang\Helper;

use FLBuilderModel;

class BeaverBuilderTextAccessor implements ITextAccessor
{
    private static $PLUGIN_NAME = 'Beaver Builder';

    public function getPluginName()
    {
        return self::$PLUGIN_NAME;
    }

    public function getTexts($post)
    {
        $texts = array();

        $layoutData = FLBuilderModel::get_layout_data(null, $post->ID);

        foreach($layoutData as $layoutObject){
            if($layoutObject->type !== 'module'){
                continue;
            }

            $settingsTextProperties = $this->getTextProperties($layoutObject->settings);

            $flattenTextProperties = $this->flattenArray($settingsTextProperties, $layoutObject->node.'_'.'settings');

            $texts = array_merge($texts, $flattenTextProperties);
        }

        return $texts;
    }

    public function setTexts($post, $texts)
    {
        $layoutData = FLBuilderModel::get_layout_data(null, $post->ID);

        foreach($texts as $id => $text){
            $object = $layoutData;
            $keys = explode('_', $id);
            $lastKeyIndex = count($keys)-1;

            foreach($keys as $index => $key){

                if($index !== $lastKeyIndex)
                {
                    if(is_array($object)){
                        $object = $object[$key];
                    }else if (is_object($object)){
                        $object = $object->{$key};
                    }

                    continue;
                }

                $object->$key = $text;
            }
        }

        FLBuilderModel::update_layout_data($layoutData, null, $post->ID);
    }

    public function prepareTranslationPost($postId, $translationPostId)
    {
        update_post_meta($translationPostId, '_fl_builder_enabled', get_post_meta($postId, '_fl_builder_enabled', true));

        $layoutData = FLBuilderModel::get_layout_data(null, $postId);
        FLBuilderModel::update_layout_data($layoutData, null, $translationPostId);

        $layoutSettings = FLBuilderModel::get_layout_settings(null, $postId);
        FLBuilderModel::update_layout_settings($layoutSettings, null, $translationPostId);
    }

    private function getTextProperties($settings){
        $texts = array();

        foreach($settings as $key => $value){
            if(stripos($key, 'text') === false && stripos($key, 'title') === false && stripos($key, 'html') === false){
                continue;
            }

            $texts[$key] = $value;
        }

        return $texts;
    }

    private function flattenArray($settingsTextProperties, $keyPrefix)
    {
        $flatten = array();

        foreach($settingsTextProperties as $key => $value){
            $flattenKey = $keyPrefix . '_' . $key;

            if(is_array($value) || is_object($value)){
                $flatten = array_merge($flatten, $this->flattenArray($value, $flattenKey));
                continue;
            }

            $flatten[$flattenKey] = $value;
        }

        return $flatten;
    }
}