<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\XmlRpc\Request;

use PHPUnit\Framework\TestCase;
use Zend\XmlRpc\Request;
use ZendTest\XmlRpc\PhpInputMock;

/**
 * @group      Zend_XmlRpc
 */
class HttpTest extends TestCase
{
    /**
     * Setup environment
     */
    public function setUp()
    {
        $this->xml = <<<EOX
<?xml version="1.0" encoding="UTF-8"?>
<methodCall>
    <methodName>test.userUpdate</methodName>
    <params>
        <param>
            <value><string>blahblahblah</string></value>
        </param>
        <param>
            <value><struct>
                <member>
                    <name>salutation</name>
                    <value><string>Felsenblöcke</string></value>
                </member>
                <member>
                    <name>firstname</name>
                    <value><string>Lépiné</string></value>
                </member>
                <member>
                    <name>lastname</name>
                    <value><string>Géranté</string></value>
                </member>
                <member>
                    <name>company</name>
                    <value><string>Zend Technologies, Inc.</string></value>
                </member>
            </struct></value>
        </param>
    </params>
</methodCall>
EOX;
        $this->request = new Request\Http();
        $this->request->loadXml($this->xml);

        $this->server = $_SERVER;
        foreach ($_SERVER as $key => $value) {
            if ('HTTP_' == substr($key, 0, 5)) {
                unset($_SERVER[$key]);
            }
        }
        $_SERVER['HTTP_USER_AGENT']     = 'Zend_XmlRpc_Client';
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['HTTP_CONTENT_TYPE']   = 'text/xml';
        $_SERVER['HTTP_CONTENT_LENGTH'] = strlen($this->xml) + 1;
        PhpInputMock::mockInput($this->xml);
    }

    /**
     * Teardown environment
     */
    public function tearDown()
    {
        $_SERVER = $this->server;
        unset($this->request);
        PhpInputMock::restoreDefault();
    }

    public function testGetRawRequest()
    {
        $this->assertEquals($this->xml, $this->request->getRawRequest());
    }

    public function testGetHeaders()
    {
        $expected = [
            'User-Agent'     => 'Zend_XmlRpc_Client',
            'Host'           => 'localhost',
            'Content-Type'   => 'text/xml',
            'Content-Length' => 958
        ];
        $this->assertEquals($expected, $this->request->getHeaders());
    }

    public function testGetFullRequest()
    {
        $expected = <<<EOT
User-Agent: Zend_XmlRpc_Client
Host: localhost
Content-Type: text/xml
Content-Length: 958

EOT;
        $expected .= $this->xml;

        $this->assertEquals($expected, $this->request->getFullRequest());
    }

    public function testExtendingClassShouldBeAbleToReceiveMethodAndParams()
    {
        $request = new TestAsset\HTTPTestExtension('foo', ['bar', 'baz']);
        $this->assertEquals('foo', $request->getMethod());
        $this->assertEquals(['bar', 'baz'], $request->getParams());
    }

    public function testHttpRequestReadsFromPhpInput()
    {
        $this->assertNull(PhpInputMock::argumentsPassedTo('stream_open'));
        $request = new Request\Http();
        list($path, $mode) = PhpInputMock::argumentsPassedTo('stream_open');
        $this->assertSame('php://input', $path);
        $this->assertSame('rb', $mode);
        $this->assertSame($this->xml, $request->getRawRequest());
    }

    public function testHttpRequestGeneratesFaultIfReadFromPhpInputFails()
    {
        PhpInputMock::methodWillReturn('stream_open', false);
        $request = new Request\Http();
        $this->assertTrue($request->isFault());
        $this->assertSame(630, $request->getFault()->getCode());
    }
}
