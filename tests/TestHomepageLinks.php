<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TestHomepageLinks extends WebTestCase
{
    public function testHomepageLinks()
    {
        $client = static::createClient();

        //make request to the homepage
        $client->request('GET', '/');

        //check if the request was made successfully
        $this->assertResponseIsSuccessful();

        //check that there is a submit button
        $this->assertSelectorTextContains('*', 'Get enlightened');

        //check that there is a redirect button to the top trusted sites page
        $this->assertSelectorTextContains('*', 'Top trusted sites');
    }
}
