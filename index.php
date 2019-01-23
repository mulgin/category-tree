<?php
require 'CategoryTree.php';
require 'CommandHandler.php';

$categoryTree = new CategoryTree();
(new CommandHandler($argv, $categoryTree))->process();