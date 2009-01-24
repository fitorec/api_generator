<?php
/* SVN FILE: $Id$ */
/**
 * Api Pages Controller 
 *
 * Handles the browsing and API docs generation for all files in an app.
 *
 * PHP versions 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright       Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link            http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package         cake
 * @subpackage      cake.api_generator.controllers
 * @since           
 * @version         
 * @modifiedby      
 * @lastmodified    
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class ApiPagesController extends ApiGeneratorAppController {
/**
 * Name property
 *
 * @var string
 */
	public $name = 'ApiPages';
/**
 * Uses array
 *
 * @var array
 */
	public $uses = array('ApiGenerator.ApiFile');
/**
 * Components array
 *
 * @var array
 **/
	public $components = array('RequestHandler');
/**
 * Helpers
 *
 * @var array
 **/
	public $helpers = array('ApiGenerator.ApiDoc', 'Html', 'Javascript');
/**
 * Browse application files and find things you would like to generate API docs for.
 *
 * @return void
 **/
	public function browse_files() {
		$currentPath = implode('/', $this->passedArgs);
		$previousPath = implode('/', array_slice($this->passedArgs, 0, count($this->passedArgs) -1));
		list($dirs, $files) = $this->ApiFile->read($this->path . $currentPath);
		$this->set(compact('dirs', 'files', 'currentPath', 'previousPath'));
	}

/**
 * all_files
 * 
 * Gets a recursive list of all files that match documentor criteria.
 *
 * @access public
 * @return void
 */
	public function list_files() {
		$files = $this->ApiFile->fileList($this->path);
		$this->set('files', $files);
	}

/**
 * Browse the classes in the application / API files.
 *
 * @return void
 **/
	public function browse_classes() {
		$this->ApiClass = ClassRegistry::init('ApiGenerator.ApiClass');
		$classIndex = $this->ApiClass->getClassIndex();
		$this->set('classIndex', $classIndex);
	}

/**
 * View the API docs for all interesting parts in a file.
 *
 * @return void
 **/
	public function view_file() {
		$currentPath = implode('/', $this->passedArgs);
		$fullPath = $this->path . $currentPath;
		$previousPath = implode('/', array_slice($this->passedArgs, 0, count($this->passedArgs) -1));
		$upOneFolder = implode('/', array_slice($this->passedArgs, 0, count($this->passedArgs) -2));

		if (!file_exists($fullPath)) {
			$this->_notFound(__('No file exists with that name', true));
		}
		try {
			$docs = $this->ApiFile->loadFile($fullPath);
		} catch(Exception $e) {
			$this->_notFound($e->getMessage());
		}

		$classIndex = ClassRegistry::init('ApiGenerator.ApiClass')->getClassIndex();
		list($dirs, $files) = $this->ApiFile->read($this->path . $previousPath);

		if (!empty($docs)) {
			$this->set('showSidebar', true);
			$this->set('sidebarElement', 'sidebar/file_sidebar');
			$this->set(compact('currentPath', 'previousPath', 'upOneFolder', 'docs', 'dirs', 'files', 'classIndex'));
		} else {
			$this->set('previousPath', $previousPath);
			$this->render('no_class');
		}
	}

/**
 * View API docs for a single class used with browse_classes
 *
 * @return void
 **/
	public function view_class($classSlug = null) {
		if (!$classSlug) {
			$this->Session->setFlash(__('No class name was given', true));
			$this->redirect($this->referer());
		}
		$this->ApiClass = ClassRegistry::init('ApiGenerator.ApiClass');
		$classInfo = $this->ApiClass->findBySlug($classSlug);

		if (empty($classInfo['ApiClass']['file_name'])) {
			$this->_notFound(__('No class exists in the index with that name', true));
		}
		try {
			$docs = $this->ApiFile->loadFile($classInfo['ApiClass']['file_name']);
			$doc = $docs['class'][$classInfo['ApiClass']['name']];
		} catch(Exception $e) {
			$this->_notFound($e->getMessage());
		}
		
		$classIndex = $this->ApiClass->getClassIndex();
		if (!empty($docs)) {
			$this->set('showSidebar', true);
			$this->set('sidebarElement', 'sidebar/class_sidebar');
			$this->set(compact('doc', 'classIndex'));
		} else {
			$this->_notFound(__("Oops, seems we couldn't get the documentation for that class.", true));
		}
	}
/**
 * View the Source for a file.
 *
 * @return void
 **/
	public function view_source($classSlug = null) {
		$this->ApiClass = ClassRegistry::init('ApiGenerator.ApiClass');
		$classInfo = $this->ApiClass->findBySlug($classSlug);

		if (empty($classInfo['ApiClass']['file_name'])) {
			$this->_notFound(__('No class exists in the index with that name', true));
		}
		$fileContents = file_get_contents($classInfo['ApiClass']['file_name']);
		$this->set('contents', $fileContents);
		$this->set('filename', $classInfo['ApiClass']['file_name']);
	}
/**
 * Search through the class index.
 *
 * @return void
 **/
	public function search() {
		$results = array();
		$this->ApiClass = ClassRegistry::init('ApiGenerator.ApiClass');
		$classList = $this->ApiClass->getClassIndex();
		if (isset($this->params['url']['query'])) {
			$query = $this->params['url']['query'];
			$results = $this->paginate('ApiClass', array('ApiClass.search_index LIKE' => '%' . $query . '%'));
		}
		$this->set(compact('results', 'classList'));
	}
/**
 * Extract all the useful config info out of the ApiConfig.
 *
 * @return void
 **/
	public function beforeRender() {
		$this->set('basePath', $this->path);
		$this->set($this->ApiFile->getExclusions());
	}
}
?>