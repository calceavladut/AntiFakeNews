<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TestTopPage extends WebTestCase
{
    public function testHomepageLinks()
    {
        $client = static::createClient();

        //make request to the top page
        $client->request('GET', '/trusted-sites');

        //check if the request was made successfully
        $this->assertResponseIsSuccessful();

        //check if the Domain field exists in table
        $this->assertSelectorTextContains('*', 'Domain');

        //check that there is a redirect button back to the home page
        $this->assertSelectorTextContains('*', 'back to homepage');
    }
}
