<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Model\Scraping;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
    	$request = $this->getRequest();

 		if ($request->isPost()) {
 			$scraping = new Scraping();
 			$cnpj = $request->getPost()->num_cnpj;
 			$render = $scraping->spider($cnpj);
 			return array("flash" => $render);
 		}

        return new ViewModel();
    }
}
