<?php

// Ambiente atual: 'dev' ou 'prod'
const APP_ENV = 'dev';

$dbConfigs = [
    'dev' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'Agente-IA-Tuquinha', // altere aqui
        'username' => 'Agente-IA-Tuquinha',         // altere aqui
        'password' => '67NPU@*ciffjwbh7',             // altere aqui
        'charset'  => 'utf8mb4',
    ],
    'prod' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'Agente-IA-Tuquinha', // altere aqui
        'username' => 'Agente-IA-Tuquinha',     // altere aqui
        'password' => '67NPU@*ciffjwbh7', // altere aqui
        'charset'  => 'utf8mb4',
    ],
];

$currentDbConfig = $dbConfigs[APP_ENV];

// Config de IA - admin preenche
const AI_PROVIDER = 'openai';
// Modelo padrão mais econômico; pode ser sobrescrito por plano/usuário
const AI_MODEL = 'gpt-4o-mini';
const AI_API_KEY = ''; // preencha em produção ou use outra forma de carregar

// Credenciais simples de admin para acesso à área /admin
const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD = 'admin123'; // troque em produção
