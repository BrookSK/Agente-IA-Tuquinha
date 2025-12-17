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
const AI_API_KEY = ''; // chave da API OpenAI (fallback, normalmente configurada via painel admin)
const ANTHROPIC_API_KEY = ''; // chave da API Anthropic (Claude), opcional; pode ser configurada também via painel admin
const MEDIA_UPLOAD_ENDPOINT = 'https://media.onsolutionsbrasil.com.br/upload.php';
const MEDIA_VIDEO_UPLOAD_ENDPOINT = 'https://media.onsolutionsbrasil.com.br/video.php';

// Realtime (Socket.IO) - servidor Node no mesmo host (ou separado) para chat em tempo real e sinalização WebRTC
// Em produção, defina um segredo forte e mantenha igual no servidor Node (env SOCKET_IO_SECRET)
const SOCKET_IO_URL = 'http://localhost:3001';
const SOCKET_IO_SECRET = 'change-me';

// Credenciais simples de admin para acesso à área /admin
const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD = 'admin123'; // troque em produção

// Credenciais do Google para criação de lives no Google Meet via API
// O admin da plataforma deve preencher estes campos com os dados do projeto no Google Cloud.
// GOOGLE_CALENDAR_CLIENT_ID e GOOGLE_CALENDAR_CLIENT_SECRET vêm da tela de "Credenciais" do Google Cloud (OAuth 2.0 Client ID).
// GOOGLE_CALENDAR_REFRESH_TOKEN é obtido após uma autorização única da conta PRO que será dona das lives.
// GOOGLE_CALENDAR_CALENDAR_ID normalmente pode ficar como 'primary' para usar a agenda principal da conta PRO.
const GOOGLE_CALENDAR_CLIENT_ID = '';
const GOOGLE_CALENDAR_CLIENT_SECRET = '';
const GOOGLE_CALENDAR_REFRESH_TOKEN = '';
const GOOGLE_CALENDAR_CALENDAR_ID = 'primary';
