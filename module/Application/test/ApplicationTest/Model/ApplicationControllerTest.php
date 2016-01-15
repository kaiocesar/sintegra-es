<?php
namespace ApplicationTest\Model;

use PHPUnit_Framework_TestCase;
use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Dom\Query;
use Zend\Debug\Debug;
use Application\Model\Scraping;

class ApplicationTest extends PHPUnit_Framework_TestCase 
{

	public function testGetSectionHtml()
	{

		$scraping = new Scraping();
		$response = $scraping->fetch('31.804.115-0002-43');
		
		$dom = new Query($response);
		$secao = $dom->execute('td.secao'); // #fix
		
		$scraping = new Scraping();
		$arr = $scraping->getSectionHtml($secao);
		
		$this->assertEquals(count($arr), 3);

	}

	public function testSpiderKeyExists()
	{
		$scraping = new Scraping();
		$arr_final = $scraping->spider('31.804.115-0002-43');
		$this->assertEquals($arr_final['error'], false);
	}

	public function testSpiderKeyExistsError()
	{
		$scraping = new Scraping();
		$arr_final = $scraping->spider();
		$this->assertEquals($arr_final['error'], true);
	}


}