<?php
namespace TymFrontiers;
require_once ".appinit.php";
require_once APP_BASE_INC;
\header("Content-Type: application/json");

$post = \json_decode( \file_get_contents('php://input'), true); // json data
$post = !empty($post) ? $post : (
  !empty($_POST) ? $_POST : (
    !empty($_GET) ? $_GET : []
    )
);
$gen = new Generic;
$params = $gen->requestParam([
  "group" => ["group","username",2,28,[],"MIXED",['-','.','_']],
  "format" => ["format","option",["json","text","xml","html"]],
  "access_rank" => ["access_rank","int"]
],$post,["group"]);
if (!$params || !empty($gen->errors)) {
  $errors = (new InstanceError($gen, false))->get("requestParam",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request halted"
  ]);
  exit;
}
$rank = empty($params['access_rank']) ? (
  $session instanceof Session ? $session->access_rank() : 0
) : $params['access_rank'];
$nav_file = PRJ_ROOT . "/.system/.navigation";
if (!\file_exists($nav_file)) {
  echo \json_encode([
    "status" => "4." . \count($errors),
    "errors" => ["Navigation file not found."],
    "message" => "Request failed"
  ]);
  exit;
}
$navlist = \json_decode(\file_get_contents($nav_file));
if (!$navlist) {
  echo \json_encode([
    "status" => "4." . \count($errors),
    "errors" => ["Poorly formated JSON navigation file."],
    "message" => "Request failed"
  ]);
  exit;
}
$nav_group = $params['group'];
if (!\property_exists($navlist, $nav_group)) {
  echo \json_encode([
    "status" => "3.1",
    "errors" => ["No list found for navigation [group] '{$params['group']}'."],
    "message" => "Request failed"
  ]);
  exit;
}
// \var_dump($navlist->$nav_group);
$nav_output = [];
foreach ($navlist->$nav_group as $path=>$link) {
  if (
    ((bool)$link->strict_access && $link->access_rank == $rank)
    || (!(bool)$link->strict_access && $link->access_rank <= $rank)
  ) {
    $nav_output[] = [
      "title" => $link->title,
      "path" => $path,
      "link" => $path,
      "newtab" => empty($link->newtab) ? false : (bool)$link->newtab,
      "onclick" => $link->onclick,
      "icon" => $link->icon,
      "name" => $link->name,
      "classname" => $link->classname
    ];
  }
}

echo \json_encode([
  "status" => "0.0",
  "errors" => [],
  "message" => "Request completed",
  "navlist" => $nav_output
]);
exit;
