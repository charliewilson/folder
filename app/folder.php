<?php

namespace folder;

require_once("vendor/autoload.php");
require_once("objects/objects.php");
require_once("controllers/controllers.php");
require_once("theme/theme.php");

use PDO;
use Exception;

use AltoRouter;
use Delight\Auth\Auth;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;
use Twig\Extra\Intl\IntlExtension;

class App {

  public $db;
  public $router;
  public $twig;
  public $theme;
  public $auth;
  public $user;
  public $appData;
  
  public $pageController;
  public $photoController;
  public $themeController;

  function __construct(PDO $db = null) {

    //if an existing PDO obj is passed (to avoid creating unneccessary PDO connections), use it.
    //otherwise, create a new one.
    $this->db = ($db) ? $db : new PDO('sqlite:'.$_SERVER['DOCUMENT_ROOT'].'/app/folder.db');
    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $this->router = new AltoRouter();
    $this->router->addMatchTypes(array('tag' => '[0-9A-Za-z\-_]++'));

    //admin twig environment
    $this->twig = new Environment((new FilesystemLoader('app/views')),[
      'debug' => true
    ]);
    $this->twig->addExtension(new DebugExtension());
    $this->twig->addExtension(new IntlExtension());

    //theme twig environment
    $this->theme = new Environment((new FilesystemLoader('theme')),[
      'debug' => true
    ]);
    $this->theme->addExtension(new DebugExtension());
    $this->theme->addExtension(new IntlExtension());

    $this->auth = new Auth($this->db);
    $this->user = new User($this->db);
    $this->appData = new appData;
    
    $this->pageController = new PageController($this);
    $this->photoController = new PhotoController($this);
    $this->themeController = new ThemeController($this);
  }

  function run() {
    //Comment these out if you're in production!
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    //Required for parsing dates, change to your relevant timezone.
    date_default_timezone_set('Europe/London');

    //Setup routes
    try {
      $this->router->addRoutes([
        //Login Routes
        ['GET', '/login', 'loginGet'],
        ['GET', '/logout', 'logoutGet'],
        ['POST', '/login', 'loginPost'],

        //Admin Routes
        ['GET', '/folder', 'adminHomeGet'],
        ['GET', '/folder/page/[i:page]', 'adminHomePageGet'],
        ['GET', '/folder/newphoto', 'adminNewPhotoGet'],
        ['POST', '/folder/newphoto', 'adminNewPhotoPost'],
        ['GET', '/folder/photo/[i:id]', 'adminEditPhotoGet'],
        ['GET', '/folder/photo/[i:id]/delete', 'adminDeletePhotoGet'],
        ['POST', '/folder/photo/[i:id]', 'adminEditPhotoPost'],

        //Misc Frontend Routes
        ['GET', '/rss', 'rssGet'],

        //Debugging - sending POST data to /dump will show the contents
        ['POST', '/dump', 'dumpPost'],

        //Theme Routes - go last so all of the above cannot be overridden
        ...$this->themeController->routes()
      ]);

    } catch (Exception $e) {
      $this->pageController->errorMessage($e->getMessage());
    }

    // match current request url
    $match = $this->router->match();

    // call the mapped pageController method or throw a 404
    if (is_array($match)) {
      if (method_exists($this->themeController, $match['target'])) {
        call_user_func([$this->themeController, $match['target']], $match['params']);
      } else {
        call_user_func([$this->pageController, $match['target']], $match['params']);
      }
    } else {
      $this->pageController->pageNotFound();
    }
  }

}

class AppData {

  public $appName, $version, $postsPerPage;

  function __construct() {
    $this->appName = 'folder';
    $this->version = '1.3';
    //instagram does 24 per load...
    $this->postsPerPage = 24;
  }

  public function get() {
    return [
      "name" => $this->appName,
      "version" => $this->version,
      "year" => date("Y"),
      "postsPerPage" => $this->postsPerPage
    ];
  }

}

class User {

  private $db;

  function __construct(PDO $db) {
    $this->db = $db;
  }

  // Confirms that the email exists and password is correct.
  // Returns true if correct, false in any other case.
  public function confirmDetails($email, $pass) {
    try {
      $q = $this->db->prepare("
        SELECT `email`, `password`
        FROM `users`
        WHERE `email` = :email
      ");

      $q->execute([
        ':email' => filter_var($email,FILTER_SANITIZE_EMAIL)
      ]);

      if ($q) {
        $data = $q->fetch();
        return (password_verify(filter_var($pass, FILTER_SANITIZE_STRING), $data['password'])) ? true : false;
      } else {
        return false;
      }
    } catch (Exception $e) {
      return false;
    }

  }
}