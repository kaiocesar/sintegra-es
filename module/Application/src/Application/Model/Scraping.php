<?php
namespace Application\Model;

use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Dom\Query;

class Scraping
{

	public function fetch($cnpj)
	{
		$client = new Client('http://www.sintegra.es.gov.br/resultado.php');
		$client->setMethod(Request::METHOD_POST);
		$client->setParameterPost([
			'num_cnpj' => $cnpj,
			'botao'	   => 'Consultar',
			'num_ie'   => '',
		]);

		$response = $client->send();

		return $response;

	}


	public function sanitize_cols($col)
	{
		$newcol = "";

		// by CodeIgniter :)
		$foreign_characters = array(
			'/ä|æ|ǽ/' => 'ae',
			'/ö|œ/' => 'oe',
			'/ü/' => 'ue',
			'/Ä/' => 'Ae',
			'/Ü/' => 'Ue',
			'/Ö/' => 'Oe',
			'/À|Á|Â|Ã|Ä|Å|Ǻ|Ā|Ă|Ą|Ǎ/' => 'A',
			'/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª/' => 'a',
			'/Ç|Ć|Ĉ|Ċ|Č/' => 'C',
			'/ç|ć|ĉ|ċ|č/' => 'c',
			'/Ð|Ď|Đ/' => 'D',
			'/ð|ď|đ/' => 'd',
			'/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě/' => 'E',
			'/è|é|ê|ë|ē|ĕ|ė|ę|ě/' => 'e',
			'/Ĝ|Ğ|Ġ|Ģ/' => 'G',
			'/ĝ|ğ|ġ|ģ/' => 'g',
			'/Ĥ|Ħ/' => 'H',
			'/ĥ|ħ/' => 'h',
			'/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ/' => 'I',
			'/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı/' => 'i',
			'/Ĵ/' => 'J',
			'/ĵ/' => 'j',
			'/Ķ/' => 'K',
			'/ķ/' => 'k',
			'/Ĺ|Ļ|Ľ|Ŀ|Ł/' => 'L',
			'/ĺ|ļ|ľ|ŀ|ł/' => 'l',
			'/Ñ|Ń|Ņ|Ň/' => 'N',
			'/ñ|ń|ņ|ň|ŉ/' => 'n',
			'/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ/' => 'O',
			'/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º/' => 'o',
			'/Ŕ|Ŗ|Ř/' => 'R',
			'/ŕ|ŗ|ř/' => 'r',
			'/Ś|Ŝ|Ş|Š/' => 'S',
			'/ś|ŝ|ş|š|ſ/' => 's',
			'/Ţ|Ť|Ŧ/' => 'T',
			'/ţ|ť|ŧ/' => 't',
			'/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ/' => 'U',
			'/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ/' => 'u',
			'/Ý|Ÿ|Ŷ/' => 'Y',
			'/ý|ÿ|ŷ/' => 'y',
			'/Ŵ/' => 'W',
			'/ŵ/' => 'w',
			'/Ź|Ż|Ž/' => 'Z',
			'/ź|ż|ž/' => 'z',
			'/Æ|Ǽ/' => 'AE',
			'/ß/'=> 'ss',
			'/Ĳ/' => 'IJ',
			'/ĳ/' => 'ij',
			'/Œ/' => 'OE',
			'/ƒ/' => 'f',
			'/:/' => '',
		);

		$pattern = array_keys($foreign_characters);
		$replacement = array_values($foreign_characters);


		if (strlen($col))
		{
			$newcol = preg_replace($pattern, $replacement, $col);
		}

		return $newcol;
	}

	
	public function getSectionHtml($domElement, $args=['formatter' => 'u', 'special' => true, 'sanitize' => false]) 
	{
		$elements = [];

		foreach($domElement as $element) 
		{
			$val = strtoupper(trim($element->nodeValue));
			if (!empty($val))
			{
				$eLine =  ($args['formatter'] == 'u') ? strtoupper($element->nodeValue) : strtolower($element->nodeValue);

				if ($args['special'])
				{
					$eLine = preg_replace("/\s|&nbsp;/",'',htmlentities($eLine));
					$eLine = html_entity_decode($eLine);
				}

				if ($args['sanitize'])
				{
					$eLine = $this->sanitize_cols($eLine);
				}

				$elements[] = trim($eLine);
			}
		}

		return $elements;
	}

	public function spider($cnpj=null)
	{
		$arr_final = [];

		if ($this->validate_cnpj($cnpj) == false)
		{
			return ['error'=>true, 'message'=>'Numero de CNPJ incorreto.'];
		}

		try
		{
			$response = $this->fetch($cnpj);
			
			$dom = new Query($response);

			$results = $dom->execute('table.resultado'); // #seed1
			$secao = $dom->execute('td.secao'); // #seed2
			$tables = $dom->execute('table.resultado table');  // #seed3

			if(count($results) == 1 && count($secao) == 3)
			{

				$titulos = $dom->execute('td.titulo');
				$valores = $dom->execute('td.valor');

				$scraping = new Scraping();
				$arr_tt = $scraping->getSectionHtml($titulos, ['formatter' => 'l','special' => true,'sanitize' => true]);
				$arr_vl = $scraping->getSectionHtml($valores, ['formatter' => 'l','special' => false,'sanitize' => false]);

				if (count($arr_vl) == 18)
				{
					unset($arr_vl[count($arr_vl)-1]); // #fix
				}

				if (count($arr_tt) == count($arr_vl))
				{
					$arr_final = array_combine($arr_tt, $arr_vl);
					return ['error'=>false, 'message'=>'Dados recuperados com sucesso', 'json'=>$arr_final];
				}				
			} 
			else 
			{
				return ['error'=>true, 'message'=>$cnpj.' não existente em nossa base de dados'];
			}

		} 
		catch (\Exception $e)
		{
			return $e->getMessage();
		}

		return $arr_final;

	}

	public function validate_cnpj($cnpj)
	{
		$cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
		
		if (strlen($cnpj) != 14)
		{
			return false;
		}
		for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++)
		{
			$soma += $cnpj{$i} * $j;
			$j = ($j == 2) ? 9 : $j - 1;
		}

		$resto = $soma % 11;
		
		if ($cnpj{12} != ($resto < 2 ? 0 : 11 - $resto))
		{
			return false;
		}
		
		for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++)
		{
			$soma += $cnpj{$i} * $j;
			$j = ($j == 2) ? 9 : $j - 1;
		}
		
		$resto = $soma % 11;

		return $cnpj{13} == ($resto < 2 ? 0 : 11 - $resto);
	}
	

}

