<?php

namespace Tests\Functional;

class JobAdsTest extends BaseTestCase
{
    // Random title is key to updating and deleting our test row
    private static $magicTitle = 'TbP696maMR46Za7zCKajC7rNYJB2UX';  

    /**
     * Test that the POST route returns a 201 and no errors
     *           "title": "' . self::$magicTitle . '",
     */
    public function testInsertJobAd()
    {
        $data = \json_decode( 
            '
            {
                "title": "some great title",
                "adContent": "This is \rthe ad content. It can be \\r rather lengthy and \\rcan contain control chars.",
                "notes": "This is \\rthe notes content. It can be \\r rather lengthy and \\rcan contain control chars.",
                "postedDatetime": "2018-10-20 10:11:12",
                "sourceUrl": "http://now.is.the/time",
                "byEmail": "Y",
                "employerCompanyName": "Super Fine Employer"
            }            
            ', true);
        $response = $this->runApp('POST', '/insert-job-ad', $data);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertFalse(empty($response->getBody()));
        $this->assertJsonStringEqualsJsonString((string)$response->getBody(),        
            '
            {
                "magic": "789",
                "model": {
                    "message": "Inserted job ad."
                },
                "error": "none"
            }
            ');
    }
    public function testListJobAds()
    {
        $response = $this->runApp('GET', '/list-job-ads');
        $this->assertEquals(200, $response->getStatusCode(), 'Response: ' . print_r($response, true));
        $result = json_decode((string)$response->getBody(), true);
        $this->assertArrayHasKey('model', $result);
        $this->assertTrue(is_array($result['model']));
        $this->assertTrue(is_object($result['model'][1]));
        $this->assertTrue(is_string($result['model'][1]->id));


    }

    public function testUpdateJobAd()
    {
        $data = \json_decode( 
            '
            {
                "title": "' . self::$magicTitle . '",
                "adContent": "This is the Updated ad content. It can be \\r rather lengthy and \\rcan contain control chars.",
                "notes": "This is Updated the notes content. It can be \\r rather lengthy and \\rcan contain control chars.",
                "postedDatetime": "2018-10-20 10:11:13",
                "sourceUrl": "http://now.is.the/updated/time",
                "byEmail": "N",
                "employerCompanyName": "Super Fine Updated Employer"
            }            
            ', true);
        $response = $this->runApp('POST', '/update-job-ad', $data);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString($response->getBody(),        
            '
            {
                "magic": "789",
                "model": {
                    "message": "Updated job ad."
                },
                "error": "none"
            }
            ');
    }
 }