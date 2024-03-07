<?php

class ExceptionsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException        Comodojo\Exception\CacheException
     */
    public function testCacheException() {

        throw new \Comodojo\Exception\CacheException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\CookieException
     */
    public function testCookieException() {

        throw new \Comodojo\Exception\CookieException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\DatabaseException
     */
    public function testDatabaseException() {

        throw new \Comodojo\Exception\DatabaseException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\DispatcherException
     */
    public function testDispatcherException() {

        throw new \Comodojo\Exception\DispatcherException("Test Exception", 1, null, 503, array("Allow"=>"GET,PUT"));

    }

    /**
     * @expectedException        Comodojo\Exception\HttpException
     */
    public function testHttpException() {

        throw new \Comodojo\Exception\HttpException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\IOException
     */
    public function testIOException() {

        throw new \Comodojo\Exception\IOException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\LdaphException
     */
    public function testLdaphException() {

        throw new \Comodojo\Exception\LdaphException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\MetaWeblogException
     */
    public function testMetaWeblogException() {

        throw new \Comodojo\Exception\MetaWeblogException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\RpcException
     */
    public function testRpcException() {

        throw new \Comodojo\Exception\RpcException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\ShellException
     */
    public function testShellException() {

        throw new \Comodojo\Exception\ShellException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\TaskException
     */
    public function testTaskException() {

        throw new \Comodojo\Exception\TaskException("Test Exception", 1, null, 42);

    }

    /**
     * @expectedException        Comodojo\Exception\WPException
     */
    public function testWPException() {

        throw new \Comodojo\Exception\WPException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\XMLException
     */
    public function testXMLException() {

        throw new \Comodojo\Exception\XMLException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\XmlrpcException
     */
    public function testXmlrpcException() {

        throw new \Comodojo\Exception\XmlrpcException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\ZipException
     */
    public function testZipException() {

        throw new \Comodojo\Exception\ZipException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\ComposerRetryException
     */
    public function testComposerRetryException() {

        throw new \Comodojo\Exception\ComposerRetryException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\ComposerEventException
     */
    public function testComposerEventException() {

        throw new \Comodojo\Exception\ComposerEventException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\AuthenticationException
     */
    public function testAuthenticationException() {

        throw new \Comodojo\Exception\AuthenticationException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\ConfigurationException
     */
    public function testConfigurationException() {

        throw new \Comodojo\Exception\ConfigurationException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\InstallerException
     */
    public function testInstallerException() {

        throw new \Comodojo\Exception\InstallerException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\InvalidCacheArgumentException
     */
    public function testInvalidCacheArgumentException() {

        throw new \Comodojo\Exception\InvalidCacheArgumentException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\SimpleCacheException
     */
    public function testSimpleCacheException() {

        throw new \Comodojo\Exception\SimpleCacheException("Test Exception", 1);

    }

    /**
     * @expectedException        Comodojo\Exception\InvalidSimpleCacheArgumentException
     */
    public function testInvalidSimpleCacheArgumentException() {

        throw new \Comodojo\Exception\InvalidSimpleCacheArgumentException("Test Exception", 1);

    }

}
