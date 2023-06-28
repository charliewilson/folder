<?php

namespace Folder;

require_once("theme/theme.php");

use Folder\Controller\PageController;
use Folder\Controller\PhotoController;
use Folder\Controller\ThemeController;
use PDO;
use Exception;

use AltoRouter;
use Delight\Auth\Auth;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;
use Twig\Extra\Intl\IntlExtension;

class App {

  public PDO $db;
  public AltoRouter $router;
  public Environment $twig;
  public Environment $theme;
  public Auth $auth;
  public AppData $appData;
  
  public PageController $pageController;
  public PhotoController $photoController;
  public ThemeController $themeController;

  function __construct() {

    //if an existing PDO obj is passed (to avoid creating unnecessary PDO connections), use it.
    //otherwise, create a new one.
    $this->db = new PDO('sqlite:'.$_SERVER['DOCUMENT_ROOT'].'/src/folder.db');
    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $this->router = new AltoRouter();
    $this->router->addMatchTypes(array('tag' => '[0-9A-Za-z\-_&%]++'));

    //Setup Folder Twig environment
    $this->twig = new Environment((new FilesystemLoader('src/views')),[
      'debug' => true
    ]);
    $this->twig->addExtension(new DebugExtension());
    $this->twig->addExtension(new IntlExtension());

    //Setup theme Twig environment
    $this->theme = new Environment((new FilesystemLoader('theme')),[
      'debug' => true
    ]);
    $this->theme->addExtension(new DebugExtension());
    $this->theme->addExtension(new IntlExtension());

    $this->auth = new Auth($this->db);
    $this->appData = new AppData;

    $this->pageController = new PageController($this);
    $this->photoController = new PhotoController($this);
    $this->themeController = new ThemeController($this);
  }

  function run() {

    //Comment these out if you're in production (for now)!
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

    // call the mapped ThemeController or PageController method, or throw a 404
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

