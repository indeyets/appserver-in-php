<?php
namespace AiP\Middleware;

use AiP\Common\StringStream\Keeper;

class HTTPParserTest extends \PHPUnit_Framework_TestCase {

    public function testGET()
    {
        $test = $this;

        $parser = new HTTPParser(function($context) use ($test) {
            $test->assertArrayHasKey('_GET', $context);
            $test->assertArrayHasKey('_COOKIE', $context);
            $test->assertArrayNotHasKey('_POST', $context);
            $test->assertArrayNotHasKey('_FILES', $context);

            $test->assertInternalType('array', $context['_GET']);
            $test->assertInstanceOf('\AiP\Middleware\HTTPParser\Cookies', $context['_COOKIE']);

            $test->assertEmpty($context['_GET']);
            $test->assertEmpty($context['_COOKIE']->__toArray());
        });

        $parser(
            [
                'env' => [
                    'REQUEST_METHOD' => 'GET',
                    'QUERY_STRING' => ''
                ]
            ]
        );

        $parser = new HTTPParser(function($context) use ($test) {
            $test->assertArrayHasKey('_GET', $context);
            $test->assertArrayHasKey('_COOKIE', $context);

            $test->assertInternalType('array', $context['_GET']);
            $test->assertInstanceOf('\AiP\Middleware\HTTPParser\Cookies', $context['_COOKIE']);

            $test->assertEquals(
                ['abc' => 'def', 'ghi' => 'jkl', 'a' => ['b', 'c']],
                $context['_GET']
            );
        });

        $parser(
            [
                'env' => [
                    'REQUEST_METHOD' => 'GET',
                    'QUERY_STRING' => 'abc=def&ghi=jkl&a[]=b&a[]=c'
                ]
            ]
        );
    }

    public function testPOST()
    {
        $test = $this;

        $parser = new HTTPParser(function($context) use ($test) {
            $test->assertArrayHasKey('_GET', $context);
            $test->assertArrayHasKey('_COOKIE', $context);
            $test->assertArrayHasKey('_POST', $context);
            $test->assertArrayHasKey('_FILES', $context);

            $test->assertEmpty($context['_GET']);
            $test->assertEmpty($context['_COOKIE']->__toArray());
            $test->assertEmpty($context['_POST']);
            $test->assertEmpty($context['_FILES']);
        });

        $parser(
            [
                'env' => [
                    'REQUEST_METHOD' => 'POST',
                    'QUERY_STRING' => '',
                    'CONTENT_LENGTH' => 0
                ],
                'stdin' => Keeper::create('')->fopen()
            ]
        );

        $parser = new HTTPParser(function($context) use ($test) {
            $test->assertEquals(
                ['abc' => 'def', 'ghi' => 'jkl', 'a' => ['b', 'c']],
                $context['_POST']
            );
        });

        $parser(
            [
                'env' => [
                    'REQUEST_METHOD' => 'POST',
                    'QUERY_STRING' => '',
                    'CONTENT_LENGTH' => 27
                ],
                'stdin' => Keeper::create('abc=def&ghi=jkl&a[]=b&a[]=c')->fopen()
            ]
        );

        $parser = new HTTPParser(function($context) use ($test) {
            $test->assertEquals(
                ['abc' => 'def', 'ghi' => 'jkl', 'a' => ['b', 'c']],
                $context['_POST']
            );
        });

        $parser(
            [
                'env' => [
                    'REQUEST_METHOD' => 'POST',
                    'QUERY_STRING' => '',
                    'CONTENT_LENGTH' => 27,
                    'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=utf-8'
                ],
                'stdin' => Keeper::create('abc=def&ghi=jkl&a[]=b&a[]=c')->fopen()
            ]
        );

        $parser = new HTTPParser(function($context) use ($test) {
            $test->assertEmpty($context['_POST']);
        });

        $parser(
            [
                'env' => [
                    'REQUEST_METHOD' => 'POST',
                    'QUERY_STRING' => '',
                    'CONTENT_LENGTH' => 27,
                    'CONTENT_TYPE' => 'foo/bar'
                ],
                'stdin' => Keeper::create('abc=def&ghi=jkl&a[]=b&a[]=c')->fopen()
            ]
        );
    }
}
