<?php

use Marvin\Marvin\Test\FunctionalTestCase;

class AdminTest extends FunctionalTestCase
{
    public function testArticlesList()
    {
        $client = $this->createClient();
        $this->logIn($client);
        $client->request('GET', '/admin/articles');

        $this->assertTrue($client->getResponse()->isOk());
    }

    public function testNewArticle()
    {
        $client = $this->createClient();
        $this->logIn($client);
        $crawler = $client->request('GET', '/admin/articles/form');

        $this->assertTrue($client->getResponse()->isOk());

        $form = $crawler->selectButton('save')->form();
        $crawler = $client->submit($form, array(
            'form[name]' => 'Test article',
        ));

        $this->assertTrue($client->getResponse()->isOk());

        $crawler = $client->request('GET', '/admin/articles');
        $this->assertCount(2, $crawler->filter('#articles tbody tr'));
        $this->assertEquals('Test article', $crawler->filter('table#articles tbody tr:first-child td:first-child')->text());
    }

    public function testEditArticleWithExistingSlug()
    {
        $this->app['db']->executeUpdate("INSERT INTO article (page_id, name, slug, content, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)", array(
            1,
            "Test article",
            "test-article",
            "",
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
        ));

        $client = $this->createClient();
        $this->logIn($client);
        $crawler = $client->request('GET', '/admin/articles/form');

        $this->assertTrue($client->getResponse()->isOk());

        $form = $crawler->selectButton('save')->form();
        $crawler = $client->submit($form, array(
            'form[name]' => 'Test article',
        ));

        $this->assertTrue($client->getResponse()->isOk());

        $data = $this->app['db']->fetchAssoc("SELECT * FROM article WHERE id = 3");
        $this->assertEquals('test-article-2', $data['slug']);
    }

    public function testEditArticle()
    {
        $this->app['db']->executeUpdate("INSERT INTO article (page_id, name, slug, content, sort, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)", array(
            1,
            "Test article",
            "test-article",
            "",
            2,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
        ));

        $client = $this->createClient();
        $this->logIn($client);
        $crawler = $client->request('GET', '/admin/articles/form/2');

        $this->assertTrue($client->getResponse()->isOk());

        $form = $crawler->selectButton('save')->form();
        $crawler = $client->submit($form, array(
            'form[name]' => 'Test article 2',
        ));

        $this->assertTrue($client->getResponse()->isOk());

        $crawler = $client->request('GET', '/admin/articles');
        $this->assertCount(2, $crawler->filter('#articles tbody tr'));
        $this->assertEquals('Test article 2', $crawler->filter('table#articles tbody tr:first-child td:first-child')->text());
    }

    public function testDeleteArticle()
    {
        $this->app['db']->executeUpdate("INSERT INTO article (page_id, name, slug, content, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)", array(
            1,
            "Test article",
            "test-article",
            "",
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
        ));

        $client = $this->createClient();
        $this->logIn($client);
        $crawler = $client->request('GET', '/admin/articles/delete/1');

        $this->assertTrue($client->getResponse()->isOk());

        $crawler = $client->request('GET', '/admin/articles');
        $this->assertCount(1, $crawler->filter('#articles tbody tr'));
    }

    public function testMovePage()
    {
        $this->app['db']->executeUpdate("INSERT INTO article (page_id, name, slug, content, sort, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)", array(
            1,
            "Test article",
            "test-article",
            "",
            2,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
        ));

        $this->app['db']->executeUpdate("INSERT INTO page (name, slug, content, sort, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)", array(
            "Page",
            "page",
            "",
            2,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
        ));

        $this->app['db']->executeUpdate("INSERT INTO article (page_id, name, slug, content, sort, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)", array(
            2,
            "Test article 2",
            "test-article-2",
            "",
            1,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
        ));

        $this->app['db']->executeUpdate("INSERT INTO article (page_id, name, slug, content, sort, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)", array(
            2,
            "Test article 3",
            "test-article-3",
            "",
            2,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
        ));

        $client = $this->createClient();
        $this->logIn($client);
        $crawler = $client->request('POST', '/admin/articles/move/2/down');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('Hello World!', $crawler->filter('table#articles tbody tr:first-child td:first-child')->text());
        $this->assertEquals('Test article', $crawler->filter('table#articles tbody tr:nth-child(2) td:first-child')->text());
        $this->assertEquals('Test article 3', $crawler->filter('table#articles tbody tr:nth-child(3) td:first-child')->text());
        $this->assertEquals('Test article 2', $crawler->filter('table#articles tbody tr:last-child td:first-child')->text());

        $crawler = $client->request('POST', '/admin/articles/move/2/up');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('Test article', $crawler->filter('table#articles tbody tr:first-child td:first-child')->text());
        $this->assertEquals('Hello World!', $crawler->filter('table#articles tbody tr:nth-child(2) td:first-child')->text());
        $this->assertEquals('Test article 3', $crawler->filter('table#articles tbody tr:nth-child(3) td:first-child')->text());
        $this->assertEquals('Test article 2', $crawler->filter('table#articles tbody tr:last-child td:first-child')->text());

        $crawler = $client->request('POST', '/admin/articles/move/4/up');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('Test article', $crawler->filter('table#articles tbody tr:first-child td:first-child')->text());
        $this->assertEquals('Hello World!', $crawler->filter('table#articles tbody tr:nth-child(2) td:first-child')->text());
        $this->assertEquals('Test article 3', $crawler->filter('table#articles tbody tr:nth-child(3) td:first-child')->text());
        $this->assertEquals('Test article 2', $crawler->filter('table#articles tbody tr:last-child td:first-child')->text());
    }

}
