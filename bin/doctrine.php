#!/usr/bin/env php
<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
// replace with path to your own project bootstrap file
include_once __DIR__ . '/../cli-config.php';
//require_once 'bootstrap.php';
//// replace with mechanism to retrieve EntityManager in your app
//$entityManager = GetEntityManager();
//$commands = [
//    // If you want to add your own custom console commands,
//    // you can do so here.
//];
//ConsoleRunner::run(
//    new SingleManagerProvider($entityManager),
//    $commands
//);