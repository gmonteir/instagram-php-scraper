<?php

namespace InstagramScraper;

class InstagramScraper implements InstagramDataProvider
{
    const INSTAGRAM_URL = 'https://www.instagram.com/';

    function getAccount($username)
    {

        $response = \Unirest\Request::get(self::INSTAGRAM_URL . $username);
        if ($response->code === 404) {
            throw new InstagramException('Account with this username does not exist.');
        }
        if ($response->code !== 200) {
            throw new InstagramException('Response code is not equal 200. Something went wrong. Please report issue.');
        }
        $arr = explode('window._sharedData = ', $response->body);
        $json = explode(';</script>', $arr[1])[0];
        $userArray = json_decode($json, true);
        if (!is_array($userArray)) {
            throw new InstagramException('Response decoding failed. Returned data corrupted or this library outdated. Please report issue');
        }
        if (!isset($userArray['entry_data']['ProfilePage'])) {
            throw new InstagramException('Account with this username does not exist');

        }
        return new Account($userArray['entry_data']['ProfilePage'][0]['user']);
    }

    function getMedias($username, $count = 20)
    {
        $index = 0;
        $medias = [];
        $maxId = '';
        $isMoreAvailable = true;
        while ($index < $count && $isMoreAvailable) {
            $response = \Unirest\Request::get(self::INSTAGRAM_URL . $username . '/media/?max_id=' . $maxId);
            if ($response->code !== 200) {
                throw new InstagramException('Response code is not equal 200. Something went wrong. Please report issue.');
            }

            $arr = json_decode($response->raw_body, true);
            if (!is_array($arr)) {
                throw new InstagramException('Response decoding failed. Returned data corrupted or this library outdated. Please report issue');
            }
            if (count($arr['items']) === 0) {
                return [];
            }
            foreach ($arr['items'] as $mediaArray) {
                if ($index === $count) {
                    return $medias;
                }
                $medias[] = new Media($mediaArray);
                $index++;
            }
            $maxId = $arr['items'][count($arr['items']) - 1]['id'];
            $isMoreAvailable = $arr['more_available'];
        }
        return $medias;
    }
}