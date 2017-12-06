<?php
namespace test\http\view;
use metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\http\view\XSendFileView;

class XSendFileViewTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$XSendFileView = new XSendFileView;
		$this->assertInstanceOf('metadigit\core\http\view\XSendFileView', $XSendFileView);
		return $XSendFileView;
	}

	/**
	 * @depends testConstructor
	 * @param XSendFileView $XSendFileView
	 */
	function testRender(XSendFileView $XSendFileView) {
		header_remove();
		$Req = new Request;
		$Res = new Response;
		$XSendFileView->render($Req, $Res, 'xsendfile.txt');
		$headers = headers_list();
//		$this->assertContains('X-Accel-Redirect: '.MOCK_DIR.'/http/templates/test.txt', $headers);
//		$this->assertContains('X-Sendfile: '.MOCK_DIR.'/http/templates/test.txt', $headers);
		$this->assertEquals(0, $Res->getSize());
		header_remove();
	}

	/**
	 * @depends testConstructor
	 * @expectedException \metadigit\core\http\Exception
	 * @expectedExceptionCode 201
	 * @param XSendFileView $XSendFileView
	 */
	function testRenderException(XSendFileView $XSendFileView) {
		$Req = new Request;
		$Res = new Response;
		$XSendFileView->render($Req, $Res, 'not-exists.txt');
	}
}