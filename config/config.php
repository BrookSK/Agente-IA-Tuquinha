<?php

// Ambiente atual: 'dev' ou 'prod'
const APP_ENV = 'dev';

$dbConfigs = [
    'dev' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'tuquinha_dev', // altere aqui
        'username' => 'root',         // altere aqui
        'password' => '',             // altere aqui
        'charset'  => 'utf8mb4',
    ],
    'prod' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'tuquinha_prod', // altere aqui
        'username' => 'prod_user',     // altere aqui
        'password' => 'prod_password', // altere aqui
        'charset'  => 'utf8mb4',
    ],
];

$currentDbConfig = $dbConfigs[APP_ENV];

// Config de IA - admin preenche
const AI_PROVIDER = 'openai';
// Modelo padrão mais econômico; pode ser sobrescrito por plano/usuário
const AI_MODEL = 'gpt-4o-mini';
const AI_API_KEY = ''; // preencha em produção ou use outra forma de carregar
