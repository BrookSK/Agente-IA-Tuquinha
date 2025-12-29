<?php

namespace App\Models;

use App\Models\Setting;
use App\Models\Personality;

class TuquinhaEngine
{
    private string $systemPrompt;

    public function __construct()
    {
        $this->systemPrompt = $this->buildSystemPrompt();
    }

    public function generateResponse(array $messages, ?string $model = null): string
    {
        // Compatibilidade: mantém a assinatura antiga usando apenas o system prompt padrão
        $result = $this->generateResponseWithContext($messages, $model, null, null, null);
        if (is_array($result) && isset($result['content']) && is_string($result['content'])) {
            return $result['content'];
        }

        return is_string($result) ? $result : '';
    }

    public function generateResponseWithContext(array $messages, ?string $model = null, ?array $user = null, ?array $conversationSettings = null, ?array $persona = null): array
    {
        $configuredModel = Setting::get('openai_default_model', AI_MODEL);
        $modelToUse = $model ?: $configuredModel;

        // Decide provedor com base no nome do modelo
        if ($this->isClaudeModel($modelToUse)) {
            return $this->callAnthropicClaude($messages, $modelToUse, $user, $conversationSettings, $persona);
        }

        return $this->callOpenAI($messages, $modelToUse, $user, $conversationSettings, $persona);
    }

    private function isClaudeModel(string $model): bool
    {
        return str_starts_with($model, 'claude-');
    }

    private function callOpenAI(array $messages, string $model, ?array $user, ?array $conversationSettings, ?array $persona): array
    {
        $configuredApiKey = Setting::get('openai_api_key', AI_API_KEY);

        if (empty($configuredApiKey)) {
            return [
                'content' => $this->fallbackResponse($messages),
                'total_tokens' => 0,
            ];
        }

        $payloadMessages = [];
        $payloadMessages[] = [
            'role' => 'system',
            'content' => $this->buildSystemPromptWithContext($user, $conversationSettings, $persona),
        ];

        foreach ($messages as $m) {
            if (!isset($m['role'], $m['content'])) {
                continue;
            }
            if ($m['role'] !== 'user' && $m['role'] !== 'assistant') {
                continue;
            }
            $payloadMessages[] = [
                'role' => $m['role'],
                'content' => $m['content'],
            ];
        }

        $body = json_encode([
            'model' => $model,
            'messages' => $payloadMessages,
            'temperature' => 0.7,
        ]);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $configuredApiKey,
            ],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 30,
        ]);

        $result = curl_exec($ch);

        if ($result === false) {
            curl_close($ch);
            return [
                'content' => $this->fallbackResponse($messages),
                'total_tokens' => 0,
            ];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'content' => $this->fallbackResponse($messages),
                'total_tokens' => 0,
            ];
        }

        $data = json_decode($result, true);
        $content = $data['choices'][0]['message']['content'] ?? null;
        $usageTotal = isset($data['usage']['total_tokens']) ? (int)$data['usage']['total_tokens'] : 0;

        if (!is_string($content) || $content === '') {
            return [
                'content' => $this->fallbackResponse($messages),
                'total_tokens' => 0,
            ];
        }

        return [
            'content' => $content,
            'total_tokens' => $usageTotal,
        ];
    }

    private function callAnthropicClaude(array $messages, string $model, ?array $user, ?array $conversationSettings, ?array $persona): array
    {
        $apiKey = Setting::get('anthropic_api_key', ANTHROPIC_API_KEY);
        if (empty($apiKey)) {
            return [
                'content' => $this->fallbackResponse($messages),
                'total_tokens' => 0,
            ];
        }

        $systemPrompt = $this->buildSystemPromptWithContext($user, $conversationSettings, $persona);

        $claudeMessages = [];
        foreach ($messages as $m) {
            if (!isset($m['role'], $m['content'])) {
                continue;
            }
            if ($m['role'] !== 'user' && $m['role'] !== 'assistant') {
                continue;
            }
            $role = $m['role'] === 'assistant' ? 'assistant' : 'user';
            $claudeMessages[] = [
                'role' => $role,
                'content' => [
                    [
                        'type' => 'text',
                        'text' => (string)$m['content'],
                    ],
                ],
            ];
        }

        $body = json_encode([
            'model' => $model,
            'system' => $systemPrompt,
            'messages' => $claudeMessages,
            'max_tokens' => 2048,
            'temperature' => 0.7,
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 30,
        ]);

        $result = curl_exec($ch);
        if ($result === false) {
            curl_close($ch);
            return [
                'content' => $this->fallbackResponse($messages),
                'total_tokens' => 0,
            ];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'content' => $this->fallbackResponse($messages),
                'total_tokens' => 0,
            ];
        }

        $data = json_decode($result, true);
        $content = null;
        if (!empty($data['content'][0]['text']) && is_string($data['content'][0]['text'])) {
            $content = $data['content'][0]['text'];
        }

        $usageTotal = 0;
        if (isset($data['usage']['input_tokens']) || isset($data['usage']['output_tokens'])) {
            $usageTotal = (int)($data['usage']['input_tokens'] ?? 0) + (int)($data['usage']['output_tokens'] ?? 0);
        }

        if (!is_string($content) || $content === '') {
            return [
                'content' => $this->fallbackResponse($messages),
                'total_tokens' => 0,
            ];
        }

        return [
            'content' => $content,
            'total_tokens' => $usageTotal,
        ];
    }

    private function fallbackResponse(array $messages): string
    {
        $lastUser = '';
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                $lastUser = trim((string)($messages[$i]['content'] ?? ''));
                break;
            }
        }

        return "Opa! Vou ser sincero com você: eu ainda não estou conectado à IA em produção, então essa aqui é uma resposta de emergência. 💡\n\n" .
            "Mas já dá pra te guiar num caminho seguro:\n\n" .
            "1. Me conta em qual fase do projeto de marca você tá (briefing, estratégia, visual, apresentação...).\n" .
            "2. Qual é a maior dúvida específica que você tem agora?\n" .
            "3. O que você já tentou fazer até aqui?\n\n" .
            "Com essas três coisas eu consigo te devolver um passo a passo bem prático. Bora lá?";
    }

    private function buildSystemPrompt(): string
    {
        $base = Setting::get('tuquinha_system_prompt', '');
        if (!is_string($base) || trim($base) === '') {
            $base = self::getDefaultPrompt();
        }

        $extra = Setting::get('tuquinha_system_prompt_extra', '');

        $parts = [];
        $trimmedBase = trim($base);
        if ($trimmedBase !== '') {
            $parts[] = $trimmedBase;
        }

        if (is_string($extra)) {
            $trimmedExtra = trim($extra);
            if ($trimmedExtra !== '') {
                $parts[] = $trimmedExtra;
            }
        }

        $prompt = implode("\n\n", $parts);

        // Regras fixas de formatação: garantem legibilidade mesmo se o admin alterar o prompt
        $formatAppendix = "\n\nFORMATAÇÃO (OBRIGATÓRIA)\n" .
            "- Sempre use quebras de linha e linhas em branco para separar blocos.\n" .
            "- Quando fizer sentido, organize em seções com títulos usando '###' (ex: ### Contexto, ### Resposta pronta, ### Próximos passos).\n" .
            "- Use listas com '-' para itens e listas numeradas para passo a passo.\n" .
            "- Separe claramente: (1) entendimento/contexto, (2) entrega/resposta pronta, (3) próximos passos/pergunta final.\n" .
            "- Evite parágrafos longos: prefira 1–3 frases por parágrafo.\n";

        if (stripos($prompt, 'FORMATAÇÃO (OBRIGATÓRIA)') === false) {
            $prompt .= $formatAppendix;
        }

        return $prompt;
    }

    private function buildSystemPromptWithContext(?array $user, ?array $conversationSettings, ?array $persona): string
    {
        $parts = [];
        $parts[] = $this->systemPrompt;

        if ($persona) {
            $personaLines = [];

            $personaName = isset($persona['name']) ? trim((string)$persona['name']) : '';
            $personaArea = isset($persona['area']) ? trim((string)$persona['area']) : '';
            $personaPrompt = isset($persona['prompt']) ? trim((string)$persona['prompt']) : '';
            $personaId = isset($persona['id']) ? (int)$persona['id'] : 0;

            if ($personaName !== '' || $personaArea !== '') {
                $title = $personaName;
                if ($personaArea !== '') {
                    $title = $title !== '' ? ($title . ' (' . $personaArea . ')') : $personaArea;
                }
                $personaLines[] = 'PERSONALIDADE ATUAL: ' . $title . '.';
            }

            if ($personaName !== '') {
                $personaLines[] = 'SEU NOME NESTE CHAT É "' . $personaName . '". Quando o usuário perguntar "qual o seu nome?", responda apenas com esse nome. Não diga que seu nome é "Tuquinha" (a menos que a personalidade se chame exatamente "Tuquinha").';
            }

            // Handoff por área: quando a pergunta não for do seu domínio, oriente o usuário a abrir um chat com a personalidade correta.
            $otherPersonas = [];
            try {
                $all = Personality::allActive();
                foreach ($all as $p) {
                    $pid = (int)($p['id'] ?? 0);
                    if ($pid > 0 && $personaId > 0 && $pid === $personaId) {
                        continue;
                    }
                    $n = trim((string)($p['name'] ?? ''));
                    $a = trim((string)($p['area'] ?? ''));
                    if ($n === '' || $a === '') {
                        continue;
                    }
                    $otherPersonas[] = $n . ' — ' . $a;
                    if (count($otherPersonas) >= 12) {
                        break;
                    }
                }
            } catch (\Throwable $e) {
                $otherPersonas = [];
            }

            if ($personaArea !== '' && $otherPersonas) {
                $personaLines[] = "OUTRAS PERSONALIDADES DISPONÍVEIS (NOME — ÁREA):\n- " . implode("\n- ", $otherPersonas);
                $personaLines[] = 'REGRA DE ESPECIALIDADE: responda sempre priorizando sua área (' . $personaArea . '). Se o usuário fizer uma pergunta claramente fora da sua área e que se encaixe melhor na área de outra personalidade, recomende explicitamente abrir um NOVO CHAT com a personalidade correta, citando o NOME exato e a ÁREA (por exemplo: "Para isso, abra um chat com \"NOME\" (ÁREA)"). Em seguida, se possível, dê apenas uma orientação geral e curta, e indique o que ele deve perguntar no chat da personalidade recomendada.';
            }

            if ($personaPrompt !== '') {
                $personaLines[] = $personaPrompt;
            }

            if ($personaLines) {
                $parts[] = implode("\n\n", $personaLines);
            }
        }

        if ($user) {
            $userLines = [];

            $name = isset($user['name']) ? trim((string)$user['name']) : '';
            $preferredName = isset($user['preferred_name']) ? trim((string)$user['preferred_name']) : '';

            if ($preferredName !== '' || $name !== '') {
                if ($preferredName !== '' && $name !== '' && $preferredName !== $name) {
                    $userLines[] = 'O usuário se chama ' . $name . ' e prefere ser chamado de ' . $preferredName . ' nas respostas.';
                } elseif ($preferredName !== '') {
                    $userLines[] = 'O usuário prefere ser chamado de ' . $preferredName . ' nas respostas.';
                } elseif ($name !== '') {
                    $userLines[] = 'O nome do usuário é ' . $name . '.';
                }
            }

            $globalMemory = isset($user['global_memory']) ? trim((string)$user['global_memory']) : '';
            if ($globalMemory !== '') {
                $userLines[] = "Memórias globais sobre o usuário (use como contexto fixo, não peça para ele repetir):\n" . $globalMemory;
            }

            $globalInstructions = isset($user['global_instructions']) ? trim((string)$user['global_instructions']) : '';
            if ($globalInstructions !== '') {
                $userLines[] = "Regras globais definidas pelo usuário (siga sempre que não forem conflitantes com regras de segurança):\n" . $globalInstructions;
            }

            if ($userLines) {
                $parts[] = implode("\n\n", $userLines);
            }
        }

        if ($conversationSettings) {
            $convLines = [];

            $memoryNotes = isset($conversationSettings['memory_notes']) ? trim((string)$conversationSettings['memory_notes']) : '';
            if ($memoryNotes !== '') {
                $convLines[] = "Memórias específicas deste chat (dados que devem ser considerados durante toda a conversa):\n" . $memoryNotes;
            }

            $customInstructions = isset($conversationSettings['custom_instructions']) ? trim((string)$conversationSettings['custom_instructions']) : '';
            if ($customInstructions !== '') {
                $convLines[] = "Regras específicas deste chat (estilo de resposta, papel, limites etc.):\n" . $customInstructions;
            }

            if ($convLines) {
                $parts[] = implode("\n\n", $convLines);
            }
        }

        return implode("\n\n---\n\n", $parts);
    }

    public static function getDefaultPrompt(): string
    {
        return <<<PROMPT
Você é o Tuquinha, mascote vibrante da Agência Tuca que se tornou um mentor especializado em branding e identidade visual. Sua missão é capacitar designers de todos os níveis a criar marcas autênticas, estratégicas e memoráveis.

PERSONALIDADE E TOM DE VOZ
- Energia contagiante mas profissional.
- Didático sem ser chato.
- Profundo mas acessível.
- Entusiasta genuíno de branding.
- Mentor encorajador, não professor autoritário.

REGRAS DE COMUNICAÇÃO
- Fale sempre em português do Brasil.
- Use "você" em vez de "o designer".
- Pode usar gírias moderadas, sempre com clareza.
- Use emojis de forma estratégica, nunca em excesso (✨🎯💡🚀🔥💪👀⚠️).
- Evite linguagem corporativa fria e jargões vazios.
- Explique termos técnicos de forma natural quando apareçam.

ESTRUTURA DE RESPOSTA IDEAL
Cada resposta deve seguir, na medida do possível, essa anatomia:
1) Abertura empática (1–2 linhas), reconhecendo o contexto do designer.
2) Posicionamento claro do que você vai fazer na resposta.
3) Conteúdo principal BEM organizado:
   - Use subtítulos quando fizer sentido.
   - Use listas numeradas para processos.
   - Use bullets para características e pontos-chave.
   - Use um pouco de **negrito** em palavras importantes (sem exagero).
4) Exemplo prático ou analogia, quando for relevante.
5) Próximos passos claros (o que o designer deve fazer agora).
6) Encerramento com convite ao diálogo ou checagem de entendimento.

FORMATAÇÃO (OBRIGATÓRIA)
- Sempre use quebras de linha e linhas em branco para separar blocos (não escreva tudo em um único parágrafo).
- Quando fizer sentido, organize em seções com títulos usando '###' (ex: ### Contexto, ### Resposta pronta, ### Próximos passos).
- Quando o usuário pedir "texto pronto" (legenda, copy, roteiro, etc.), coloque a entrega em um bloco separado sob o título '### Resposta pronta'.
- Termine com '### Próximos passos' e 1 pergunta objetiva para o usuário.

ARQUÉTIPOS E PERSONALIDADE
- Arquétipo primário: Mentor (Sábio) – ensina com generosidade, clareza e profundidade.
- Arquétipo secundário: Rebelde – questiona a mesmice, provoca pensamento diferente, incentiva ousadia criativa.
- Arquétipo terciário: Amigo (Cara comum) – acessível, próximo, linguagem simples, celebra junto.

O QUE VOCÊ PODE FAZER
- Consultoria estratégica de branding (posicionamento, diferenciação, arquétipos, proposta de valor).
- Orientação em identidade visual (conceito, coerência, direção criativa, não execução de arquivos finais).
- Apoio criativo (brainstorming de nomes, conceitos, paletas, tipografia, direções visuais).
- Educação e mentoria (explicar conceitos, sugerir metodologias práticas, indicar bibliografia relevante).
- Ajuda em gestão comercial de projetos de branding (precificação, proposta, escopo, alinhamento de expectativas).

O QUE VOCÊ NÃO PODE FAZER
- Não crie logotipos finais, símbolos prontos ou arquivos de produção (SVG, AI, PSD etc.).
- Não faça o trabalho completo pelo designer; foque em guiá-lo e capacitar.
- Não copie ou incentive cópia direta de outras marcas.
- Não prometa resultados impossíveis ou garantias de sucesso.

ABORDAGEM DIDÁTICA
- Sempre explique o raciocínio por trás das recomendações.
- Use analogias simples (ex: "marca é como uma pessoa", "posicionamento é onde você se senta numa festa").
- Faça perguntas estratégicas que ajudem o designer a pensar mais fundo.
- Celebre o processo, não só o resultado final.

NÍVEL DO DESIGNER
Adapte profundidade e linguagem ao nível de experiência percebido nas perguntas:
- Se for iniciante: mais passo a passo, mais exemplos, validações frequentes.
- Se for intermediário: frameworks, checklists e nuances estratégicas.
- Se for avançado: discussões mais densas, referências bibliográficas, provocações conceituais.

LIMITAÇÕES E TRANSPARÊNCIA
- Se não souber algo com segurança, admita com transparência e proponha caminhos de pesquisa ou reflexão.
- Se o pedido fugir de branding, identidade visual ou temas próximos (gestão de projetos de design, negócios de design), responda de forma breve e redirecione para sua zona de maior valor.

ESTILO DE RESPOSTA
- Comece frequentemente com frases como: "Bora lá?", "Olha só que interessante...", "Vou ser sincero com você:" ou similares.
- Use um tom motivador: encoraje, normalize erros como parte do aprendizado, celebre conquistas.
- Evite respostas secas ou robóticas; traga calor humano e contexto.

OBJETIVO FINAL
Seu sucesso é medido pelo quanto o designer:
- Entende melhor branding e identidade visual.
- Ganha confiança para tomar decisões estratégicas.
- Fica mais autônomo ao longo do tempo.
- Faz perguntas cada vez mais sofisticadas.

Siga sempre essas diretrizes em TODAS as respostas.
PROMPT;
    }

    public static function generateShortTitle(string $userText): ?string
    {
        $userText = trim($userText);
        if ($userText === '') {
            return null;
        }

        $configuredApiKey = Setting::get('openai_api_key', AI_API_KEY);
        if (empty($configuredApiKey)) {
            return null;
        }

        $configuredModel = Setting::get('openai_default_model', AI_MODEL);
        $modelToUse = $configuredModel ?: AI_MODEL;

        $messages = [
            [
                'role' => 'system',
                'content' => 'Você é um assistente que gera títulos curtos e claros para conversas de chat. Responda apenas com um título em, no máximo, 6 palavras, sem aspas.',
            ],
            [
                'role' => 'user',
                'content' => "Gere um título curto para esta conversa, baseado na primeira mensagem do usuário:\n\n" . $userText,
            ],
        ];

        $body = json_encode([
            'model' => $modelToUse,
            'messages' => $messages,
            'temperature' => 0.4,
        ]);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $configuredApiKey,
            ],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 15,
        ]);

        $result = curl_exec($ch);
        if ($result === false) {
            curl_close($ch);
            return null;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        $data = json_decode($result, true);
        $content = $data['choices'][0]['message']['content'] ?? null;
        if (!is_string($content)) {
            return null;
        }

        $title = trim($content);
        if ($title === '') {
            return null;
        }

        // Limita tamanho máximo para garantir que fique curto
        if (mb_strlen($title, 'UTF-8') > 80) {
            $title = mb_substr($title, 0, 80, 'UTF-8');
        }

        return $title;
    }
}
