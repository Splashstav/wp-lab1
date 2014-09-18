<?php
		
	function h1e( $head )
	{
		echo '<h1>' . $head . '</h1>';
	}
	
	function pre( $array )
	{
		echo '<pre>';
		print_r( $array );
		echo '</pre>';
	}
	
	abstract class Template
	{
		/**
		 * @return Template
		 */
		static function get( $path )
		{
			if ( file_exists( $path ) )
			{
				return new static( $path, file_get_contents( $path ) );
			}
			return null;
		}
	
		private $path;
		protected $tpl;
	
		final function __construct( $path, $tpl )
		{
			$this->path = $path;
			$this->tpl = $this->preLoad( $tpl );
		}
	
		protected function getPath()
		{
			return $this->path;
		}
	
		function preLoad( $tpl )
		{
			return $tpl;
		}
	
		abstract function process( $parms = array() );
	}
	
	class MarkerTpl extends Template
	{
		function process( $parms = array() )
		{
			$keys = array();
			$values = array();
				
			// '[%mark%]'
			// Переопределить preLoad так, что бы при создании шаблона в нем находились все маркеры, еще до замещения.
			// варианты:
			// 1. реглярные выражения
			// 2. поиск в строке автоматическим перебором.
	
			foreach ( $parms as $key => $val )
			{
				$keys[] = '[%' . $key . '%]';
				$values[] = $val;
			}
	
			return str_replace( $keys, $values, $this->tpl );
		}
	}
	
	class PHPTpl extends Template
	{
		function process( $parms = array() )
		{
			ob_start();
			foreach ( $parms as $key => $val )
			{
				$$key = $val;
			}
			include $this->getPath();
			$out = ob_get_clean();
			ob_end_clean();
			return $out;
		}
	}
	
	class Application
	{
		static private $instance;
		static function getInstance()
		{
			if ( is_null( self::$instance ) )
			{
				self::$instance = new self();
			}
			return self::$instance;
		}
	
		static function autoload( $class )
		{
			//$path = 'controllers/' . strtolower( $class ) . '.inc';
                        $path = dirname(__FILE__) . '/controllers/' . $class . '.php';
                        
			if ( file_exists( $path ) )
			{
				require_once $path;
			} else {
                            throw new Exception("Контоллер не найден", 404);
                        }
		}
	
		private $request;
	
		private final function __construct()
		{
			$this->request = Request::getInstance();
			spl_autoload_register( 'Application::autoload' );
		}
	
		function getRequest()
		{
			return $this->request;
		}
	
		function findContrller( $name )
		{
			if ( class_exists( $name ) )
			{
				return new $name();
			}
			return null;
		}
	
		function run()
		{
                        $request = $this->getRequest();
                        $param = explode('/', $request->getParm('url'));
                        
                        try {
                            $class = Controller::getClassName($param[0]);
                            $controller = new $class();
                            $controller->process($request);
                        } catch (Exception $e) {
                            echo $e->getMessage();
                        }
		}
	}
	
	/**
	 * Одиночка (Singleton)
	 * Хранение всех системных параметров
	 **/
	class Request
	{
		static private $instance;
		static function getInstance()
		{
			if ( is_null( self::$instance ) )
			{
				$parms = array_merge( array( 'template' => 'MarkerTpl' ), $_GET  );
                                
				self::$instance = new self($parms);
			}
			return self::$instance;
		}
	
		private $parms;
		private function __construct( Array $parms = array() )
		{
			$this->parms = $parms;
		}
	
		function getParm( $name )
		{
			if ( isset( $this->parms[$name] ) )
			{
				return $this->parms[$name];
			}
			return null;
		}
	
		function setParm( $name, $val )
		{
			$this->parms[$name] = $val;
		}
	}
	
	abstract class Controller
	{
		abstract function process( Request $request );
                
                public static function getClassName($url) {
                    return ucfirst($url) . 'Controller';
                }
	}
	
	class TestController extends Controller
	{
		function process( Request $request )
		{
			$tpl = MarkerTpl::get( 'test.tpl' );
			echo $tpl->process( array( 'name' => 'Толик' ) );
		}
	}
	
	//Application::getInstance()->run();