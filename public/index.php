<?php
declare(strict_types=1);
session_start();
require_once __DIR__.'/../src/Core/Router.php';
require_once __DIR__.'/../src/Model/Product.php';
require_once __DIR__.'/../src/Model/Category.php';
require_once __DIR__.'/../src/Repository/JsonCategoryRepository.php';
require_once __DIR__.'/../src/Repository/JsonProductRepository.php';
require_once __DIR__.'/../src/Service/CartService.php';
require_once __DIR__.'/../src/Controller/HomeController.php';
require_once __DIR__.'/../src/Controller/CatalogController.php';
require_once __DIR__.'/../src/Controller/CartController.php';
$root=dirname(__DIR__);
try {
 $products=new JsonProductRepository($root.'/dataset/products.json');
 $cartService=new CartService($products);
 $router=new Router($root.'/dataset/routes.csv'); $path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/'; $route=$router->dispatch($_SERVER['REQUEST_METHOD']??'GET',$path);
 if($route===null){http_response_code(404);$title='Страница не найдена';ob_start();require $root.'/views/not-found.php';$content=ob_get_clean();}
 else {[$name,$action]=$route;
  if($name==='HomeController'){$pageData=json_decode((string)file_get_contents($root.'/dataset/pages.json'),true,512,JSON_THROW_ON_ERROR);$controller=new HomeController($pageData);$data=$controller->$action();$viewFile=$root.'/views/home/index.php';}
  elseif($name==='CatalogController'){$categories=new JsonCategoryRepository($root.'/dataset/categories.json');$controller=new CatalogController($products,$categories);$data=$controller->$action();$title=$data['title']??'Каталог';if(!empty($data['notFound'])){http_response_code(404);$viewFile=$root.'/views/not-found.php';}else{$viewFile=$action==='show'?$root.'/views/catalog/product.php':$root.'/views/catalog/index.php';}}
  elseif($name==='CartController'){$controller=new CartController($cartService);$data=$controller->$action();$viewFile=$root.'/views/cart/index.php';}
  else throw new RuntimeException('Неизвестный контроллер.');
  extract($data,EXTR_SKIP);$title=$title??($data['shop_name']??'Настольная');ob_start();require $viewFile;$content=ob_get_clean();
 }
 $cartCount=$cartService->count();
} catch(Throwable $e){http_response_code(500);$title='Ошибка приложения';$cartCount=0;$content='<section class="hero"><h1>Не удалось открыть страницу</h1><p>Проверьте файлы проекта и dataset.</p></section>';}
require $root.'/views/layout.php';
