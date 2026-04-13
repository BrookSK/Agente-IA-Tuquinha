# API — Agenda de Marketing

API REST para integração externa da Agenda de Marketing do Tuquinha.

## Autenticação

Todas as requisições exigem um token de API no header:

```
Authorization: Bearer tuq_sua_chave_aqui
```

Gere sua chave em: **Agenda de Marketing → botão "🔌 API" → Gerar chave**.

O token é vinculado ao usuário e herda as permissões do plano dele. Se o plano não permitir a Agenda de Marketing, a API retorna `403`.

---

## Endpoints

### Listar eventos do mês

```
GET /api/marketing-calendar/events?year=2026&month=4
```

**Parâmetros (query string):**

| Param | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| year  | int  | Não         | Ano (padrão: ano atual) |
| month | int  | Não         | Mês 1-12 (padrão: mês atual) |

**Resposta (200):**

```json
{
  "ok": true,
  "year": 2026,
  "month": 4,
  "events": [
    {
      "id": 1,
      "owner_user_id": 42,
      "title": "Post de lançamento",
      "event_date": "2026-04-15",
      "event_type": "post",
      "status": "planejado",
      "responsible": "João",
      "color": "#e53935",
      "notes": "Ideias para o post...",
      "reference_links": ["https://exemplo.com"],
      "created_at": "2026-04-10 14:30:00",
      "updated_at": "2026-04-10 14:30:00"
    }
  ]
}
```

---

### Obter evento por ID

```
GET /api/marketing-calendar/events/show?id=123
```

**Resposta (200):**

```json
{
  "ok": true,
  "event": { ... }
}
```

---

### Criar evento

```
POST /api/marketing-calendar/events
Content-Type: application/json
```

**Body:**

```json
{
  "title": "Post de lançamento",
  "event_date": "2026-04-20",
  "event_type": "post",
  "status": "planejado",
  "responsible": "João",
  "color": "#e53935",
  "notes": "Ideias e referências",
  "reference_links": ["https://exemplo.com", "https://outro.com"]
}
```

| Campo           | Tipo     | Obrigatório | Descrição |
|-----------------|----------|-------------|-----------|
| title           | string   | Sim         | Título do evento |
| event_date      | string   | Sim         | Data no formato `YYYY-MM-DD` |
| event_type      | string   | Não         | `post`, `story`, `reels`, `video`, `email`, `anuncio`, `outro` (padrão: `post`) |
| status          | string   | Não         | `planejado`, `produzido`, `postado` (padrão: `planejado`) |
| responsible     | string   | Não         | Nome do responsável |
| color           | string   | Não         | Cor hex (padrão: `#e53935`) |
| notes           | string   | Não         | Notas/observações |
| reference_links | string[] | Não         | Lista de URLs de referência |

**Resposta (201):**

```json
{
  "ok": true,
  "event": { ... }
}
```

---

### Atualizar evento

```
POST /api/marketing-calendar/events/update
Content-Type: application/json
```

**Body:** Inclua `id` + os campos que deseja alterar. Campos omitidos mantêm o valor atual.

```json
{
  "id": 123,
  "title": "Novo título",
  "status": "produzido"
}
```

**Resposta (200):**

```json
{
  "ok": true,
  "event": { ... }
}
```

---

### Excluir evento

```
POST /api/marketing-calendar/events/delete
Content-Type: application/json
```

**Body:**

```json
{
  "id": 123
}
```

**Resposta (200):**

```json
{
  "ok": true
}
```

---

## Erros

Todas as respostas de erro seguem o formato:

```json
{
  "ok": false,
  "error": "Mensagem descritiva do erro."
}
```

| HTTP Status | Significado |
|-------------|-------------|
| 400         | Dados inválidos ou faltando |
| 401         | Token ausente ou inválido |
| 403         | Sem permissão (plano não permite ou assinatura inativa) |
| 404         | Evento não encontrado |

---

## Exemplo com cURL

```bash
# Listar eventos de abril/2026
curl -H "Authorization: Bearer tuq_sua_chave" \
     "https://seudominio.com/api/marketing-calendar/events?year=2026&month=4"

# Criar evento
curl -X POST \
     -H "Authorization: Bearer tuq_sua_chave" \
     -H "Content-Type: application/json" \
     -d '{"title":"Post Instagram","event_date":"2026-04-20","event_type":"post"}' \
     "https://seudominio.com/api/marketing-calendar/events"

# Atualizar evento
curl -X POST \
     -H "Authorization: Bearer tuq_sua_chave" \
     -H "Content-Type: application/json" \
     -d '{"id":123,"status":"postado"}' \
     "https://seudominio.com/api/marketing-calendar/events/update"

# Excluir evento
curl -X POST \
     -H "Authorization: Bearer tuq_sua_chave" \
     -H "Content-Type: application/json" \
     -d '{"id":123}' \
     "https://seudominio.com/api/marketing-calendar/events/delete"
```
