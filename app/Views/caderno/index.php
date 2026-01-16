<?php
/** @var array $user */
/** @var array $pages */
/** @var array|null $current */
/** @var array $shares */
/** @var array $breadcrumb */

$currentId = $current ? (int)($current['id'] ?? 0) : 0;
$currentTitle = $current ? (string)($current['title'] ?? 'Sem título') : 'Sem título';
$currentIcon = $current ? (string)($current['icon'] ?? '') : '';
$contentJson = $current ? (string)($current['content_json'] ?? '') : '';
$accessRole = $current ? strtolower((string)($current['access_role'] ?? 'owner')) : 'owner';
$canEdit = $current && ($accessRole === 'owner' || $accessRole === 'edit');
$isOwner = $current && (int)($current['owner_user_id'] ?? 0) === (int)($user['id'] ?? 0);
$isPublished = $current && !empty($current['is_published']);
$publicToken = $current ? (string)($current['public_token'] ?? '') : '';
$publicUrl = ($isPublished && $publicToken !== '') ? ('/caderno/publico?token=' . urlencode($publicToken)) : '';
$breadcrumb = isset($breadcrumb) && is_array($breadcrumb) ? $breadcrumb : [];

$pagesById = [];
if (isset($pages) && is_array($pages)) {
    foreach ($pages as $p) {
        if (!is_array($p)) {
            continue;
        }
        $pid = (int)($p['id'] ?? 0);
        if ($pid <= 0) {
            continue;
        }
        $pagesById[(string)$pid] = [
            'title' => (string)($p['title'] ?? 'Sem título'),
            'icon' => (string)($p['icon'] ?? ''),
        ];
    }
}

$activeRootId = 0;
if (!empty($breadcrumb)) {
    $firstCrumb = $breadcrumb[0] ?? null;
    if (is_array($firstCrumb) && !empty($firstCrumb['id'])) {
        $activeRootId = (int)$firstCrumb['id'];
    }
}
?>

<style>
    .notion-shell {
        display: flex;
        gap: 12px;
        min-height: calc(100vh - 64px);
    }

    @media (min-width: 721px) {
        body.notion-sidebar-collapsed .notion-shell {
            gap: 0;
        }
    }
    .notion-sidebar {
        width: 280px;
        flex: 0 0 280px;
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        background: var(--surface-card);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: width 160ms ease, flex-basis 160ms ease, opacity 160ms ease;
    }

    @media (min-width: 721px) {
        body.notion-sidebar-collapsed .notion-sidebar {
            width: 0;
            flex: 0 0 0;
            opacity: 0;
            border: none;
            margin: 0;
            padding: 0;
        }
    }
    .notion-sidebar-head {
        padding: 12px;
        border-bottom: 1px solid var(--border-subtle);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .notion-sidebar-title {
        font-weight: 750;
        font-size: 13px;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: var(--text-secondary);
    }
    .notion-btn {
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        color: var(--text-primary);
        border-radius: 10px;
        padding: 8px 10px;
        font-size: 12px;
        cursor: pointer;
        line-height: 1;
    }
    .notion-btn:focus {
        outline: 2px solid rgba(229,57,53,0.35);
        outline-offset: 2px;
    }
    .notion-toggle-sidebar {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        padding: 0;
        font-size: 16px;
        line-height: 1;
    }
    .notion-page {
        flex: 1;
        min-width: 0;
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        background: var(--surface-card);
        overflow: hidden;
    }
    .notion-page-header {
        padding: 18px 20px 10px 20px;
        border-bottom: 1px solid var(--border-subtle);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }
    .notion-title-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex: 1;
    }
    .notion-emoji {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        font-size: 18px;
        flex: 0 0 44px;
    }

    .tuq-emoji-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.65);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .tuq-emoji-modal {
        width: 100%;
        max-width: 520px;
        border-radius: 16px;
        border: 1px solid var(--border-subtle);
        background: var(--surface-card);
        box-shadow: var(--shadow-card-strong);
        overflow: hidden;
    }
    .tuq-emoji-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 12px;
        border-bottom: 1px solid var(--border-subtle);
        background: rgba(255,255,255,0.03);
    }
    body[data-theme="light"] .tuq-emoji-modal-header {
        background: rgba(15,23,42,0.03);
    }
    .tuq-emoji-grid {
        display: grid;
        grid-template-columns: repeat(10, minmax(0, 1fr));
        gap: 6px;
        padding: 10px 12px 12px 12px;
        max-height: 320px;
        overflow: auto;
    }
    .tuq-emoji-btn {
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        color: var(--text-primary);
        border-radius: 10px;
        height: 34px;
        cursor: pointer;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .tuq-emoji-btn:hover {
        border-color: rgba(229,57,53,0.55);
        box-shadow: 0 0 0 2px rgba(229,57,53,0.12);
    }
    .tuq-emoji-search {
        width: 100%;
        padding: 8px 10px;
        border-radius: 10px;
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        color: var(--text-primary);
        font-size: 13px;
        outline: none;
    }
    .tuq-emoji-hint {
        padding: 0 12px 12px 12px;
        font-size: 11px;
        color: var(--text-secondary);
    }
    .notion-title {
        width: 100%;
        border: none;
        outline: none;
        background: transparent;
        color: var(--text-primary);
        font-size: 28px;
        line-height: 1.15;
        font-weight: 800;
        padding: 6px 2px;
    }
    .notion-title::placeholder {
        color: rgba(255,255,255,0.45);
    }
    body[data-theme="light"] .notion-title::placeholder {
        color: rgba(15, 23, 42, 0.42);
    }
    .notion-page-body {
        padding: 14px 20px 26px 20px;
    }
    .notion-editor-wrap {
        max-width: 900px;
        margin: 0 auto;
        padding-left: 42px;
        padding-right: 6px;
    }

    @media (max-width: 640px) {
        .notion-editor-wrap {
            padding-left: 28px;
            padding-right: 0;
        }
    }

    /* Editor.js look (aproxima Notion) */
    .notion-editor-wrap .ce-block__content,
    .notion-editor-wrap .ce-toolbar__content {
        max-width: 900px;
    }
    .notion-editor-wrap .ce-paragraph {
        font-size: 15px;
        line-height: 1.75;
        color: var(--text-primary);
    }
    .notion-editor-wrap ::selection {
        background: rgba(229,57,53,0.35);
        color: #ffffff;
    }
    body[data-theme="light"] .notion-editor-wrap ::selection {
        background: rgba(229,57,53,0.25);
        color: #0f172a;
    }

    .notion-editor-wrap .ce-block--selected .ce-block__content,
    .notion-editor-wrap .ce-block--selected .ce-block__content * {
        color: var(--text-primary) !important;
    }
    .notion-editor-wrap .ce-block--selected .ce-block__content {
        background: rgba(255,255,255,0.04);
        border-radius: 10px;
    }
    body[data-theme="light"] .notion-editor-wrap .ce-block--selected .ce-block__content {
        background: rgba(15,23,42,0.04);
    }
    .notion-editor-wrap .ce-header {
        padding: 0.6em 0 0.2em;
    }
    .notion-editor-wrap h1.ce-header { font-size: 28px; }
    .notion-editor-wrap h2.ce-header { font-size: 22px; }
    .notion-editor-wrap h3.ce-header { font-size: 18px; }
    .notion-editor-wrap .cdx-list__item {
        padding: 2px 0;
        line-height: 1.7;
        font-size: 15px;
    }
    .notion-editor-wrap .cdx-checklist__item-text {
        font-size: 15px;
        line-height: 1.7;
    }
    .notion-editor-wrap .cdx-attaches {
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        background: rgba(255,255,255,0.06);
        color: var(--text-primary);
        box-shadow: none;
        overflow: visible;
    }
    body[data-theme="light"] .notion-editor-wrap .cdx-attaches {
        background: rgba(15,23,42,0.04);
    }
    .notion-editor-wrap .cdx-attaches__title {
        color: var(--text-primary) !important;
        font-weight: 650;
    }
    .notion-editor-wrap .cdx-attaches__size {
        color: var(--text-secondary) !important;
        opacity: 0.95;
    }
    .notion-editor-wrap .cdx-attaches__download-button {
        border-radius: 10px;
        background: rgba(255,255,255,0.08) !important;
        border: 1px solid var(--border-subtle) !important;
        color: var(--text-primary) !important;
    }
    body[data-theme="light"] .notion-editor-wrap .cdx-attaches__download-button {
        background: rgba(15,23,42,0.06) !important;
    }
    .notion-editor-wrap .cdx-attaches__download-button:hover {
        background: rgba(255,255,255,0.14) !important;
    }
    body[data-theme="light"] .notion-editor-wrap .cdx-attaches__download-button:hover {
        background: rgba(15,23,42,0.10) !important;
    }
    .notion-editor-wrap .cdx-attaches__download-button svg {
        fill: currentColor !important;
        color: var(--text-primary) !important;
        opacity: 0.95;
    }
    .notion-editor-wrap .cdx-attaches__file-icon {
        border-radius: 12px;
        border: 1px solid var(--border-subtle);
        box-shadow: none;
    }

    .notion-preview-modal {
        position: fixed;
        inset: 0;
        z-index: 100000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .notion-preview-modal.is-open {
        display: flex;
    }
    .notion-preview-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.70);
    }
    .notion-preview-card {
        position: relative;
        width: min(980px, calc(100vw - 28px));
        max-height: calc(100vh - 28px);
        border-radius: 16px;
        border: 1px solid var(--border-subtle);
        background: var(--surface-card);
        box-shadow: var(--shadow-card-strong);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .notion-preview-head {
        padding: 10px 12px;
        border-bottom: 1px solid var(--border-subtle);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .notion-preview-title {
        font-size: 13px;
        font-weight: 800;
        color: var(--text-primary);
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .notion-preview-body {
        padding: 0;
        overflow: auto;
        background: rgba(0,0,0,0.25);
        flex: 1;
        min-height: 260px;
    }
    body[data-theme="light"] .notion-preview-body {
        background: rgba(15,23,42,0.06);
    }
    /* Scrollbars (preview modal) */
    .notion-preview-body {
        scrollbar-width: thin;
        scrollbar-color: rgba(0,0,0,0.95) rgba(0,0,0,0.55);
    }
    body[data-theme="light"] .notion-preview-body {
        scrollbar-color: rgba(15,23,42,0.35) rgba(15,23,42,0.10);
    }
    .notion-preview-body::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    .notion-preview-body::-webkit-scrollbar-track {
        background: rgba(0,0,0,0.55);
        border-radius: 999px;
    }
    .notion-preview-body::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.95);
        border-radius: 999px;
        border: 2px solid rgba(0,0,0,0.55);
    }
    .notion-preview-body::-webkit-scrollbar-thumb:hover {
        background: rgba(20,20,20,0.98);
    }
    body[data-theme="light"] .notion-preview-body::-webkit-scrollbar-track {
        background: rgba(15,23,42,0.10);
    }
    body[data-theme="light"] .notion-preview-body::-webkit-scrollbar-thumb {
        background: rgba(15,23,42,0.28);
        border: 2px solid rgba(15,23,42,0.10);
    }
    body[data-theme="light"] .notion-preview-body::-webkit-scrollbar-thumb:hover {
        background: rgba(15,23,42,0.38);
    }
    .notion-preview-body img {
        display: block;
        max-width: 100%;
        height: auto;
        margin: 0 auto;
    }
    .notion-preview-frame {
        width: 100%;
        height: 70vh;
        border: none;
        display: block;
        background: transparent;
    }

    .notion-editor-wrap .cdx-quote__caption {
        display: none !important;
    }
    .notion-editor-wrap .image-tool__caption {
        display: none !important;
    }
    .notion-editor-wrap .ce-code__textarea {
        background: rgba(0,0,0,0.25);
        border: 1px solid var(--border-subtle);
        color: var(--text-primary);
        border-radius: 10px;
        font-size: 13px;
        line-height: 1.6;
    }
    .notion-editor-wrap .ce-code__textarea,
    .notion-editor-wrap .cdx-quote textarea {
        resize: none;
        overflow: hidden;
        height: auto;
        min-height: 44px;
    }
    .notion-editor-wrap .cdx-quote__text {
        height: auto;
        min-height: 44px;
        overflow: hidden;
    }
    body[data-theme="light"] .notion-editor-wrap .ce-code__textarea {
        background: rgba(15, 23, 42, 0.04);
    }

    /* Editor.js toolbars/popovers - melhorar legibilidade no tema */
    .notion-editor-wrap .ce-inline-toolbar,
    .notion-editor-wrap .ce-conversion-toolbar,
    .notion-editor-wrap .ce-popover,
    .notion-editor-wrap .cdx-settings-button,
    .notion-editor-wrap .cdx-search-field {
        font-family: inherit;
    }
    .notion-editor-wrap .ce-inline-toolbar,
    .notion-editor-wrap .ce-conversion-toolbar,
    .notion-editor-wrap .ce-popover {
        background: rgba(17,17,24,0.94) !important;
        border: 1px solid var(--border-subtle) !important;
        box-shadow: 0 18px 46px rgba(0,0,0,0.55) !important;
        backdrop-filter: blur(10px);
        z-index: 99999 !important;
    }
    body[data-theme="light"] .notion-editor-wrap .ce-inline-toolbar,
    body[data-theme="light"] .notion-editor-wrap .ce-conversion-toolbar,
    body[data-theme="light"] .notion-editor-wrap .ce-popover {
        background: rgba(255,255,255,0.98) !important;
        box-shadow: 0 18px 46px rgba(15,23,42,0.16) !important;
    }

    /* Toolbox/popover no mobile pode ser renderizado fora de .notion-editor-wrap */
    .ce-popover,
    .ce-toolbox,
    .ce-conversion-toolbar,
    .ce-inline-toolbar {
        background: rgba(17,17,24,0.94) !important;
        border: 1px solid var(--border-subtle) !important;
        box-shadow: 0 18px 46px rgba(0,0,0,0.55) !important;
        backdrop-filter: blur(10px);
        z-index: 99999 !important;
        color: var(--text-primary) !important;
    }
    body[data-theme="light"] .ce-popover,
    body[data-theme="light"] .ce-toolbox,
    body[data-theme="light"] .ce-conversion-toolbar,
    body[data-theme="light"] .ce-inline-toolbar {
        background: rgba(255,255,255,0.98) !important;
        box-shadow: 0 18px 46px rgba(15,23,42,0.16) !important;
        color: var(--text-primary) !important;
    }
    .ce-popover__item,
    .ce-popover__item-label,
    .ce-popover__item-description,
    .ce-toolbox__button,
    .ce-inline-tool,
    .ce-conversion-tool {
        color: var(--text-primary) !important;
    }
    .ce-popover__item-description,
    .ce-conversion-tool__description {
        color: var(--text-secondary) !important;
        opacity: 0.95;
    }
    .ce-toolbox__button svg,
    .ce-inline-tool svg,
    .ce-popover__item-icon svg,
    .ce-conversion-tool__icon svg {
        fill: currentColor !important;
        color: var(--text-primary) !important;
        stroke: currentColor !important;
        opacity: 0.95;
    }

    /* Popover do '+' (toolbox): scroll e ícones visíveis */
    .ce-popover {
        max-height: min(520px, calc(100vh - 24px)) !important;
        overflow: hidden !important;
    }
    .ce-popover__items {
        overflow-y: auto !important;
        max-height: calc(min(520px, calc(100vh - 24px)) - 54px) !important;
        padding-right: 4px;
        -webkit-overflow-scrolling: touch;
    }
    .ce-popover__items::-webkit-scrollbar { width: 10px; }
    .ce-popover__items::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.12);
        border-radius: 999px;
        border: 3px solid transparent;
        background-clip: padding-box;
    }
    body[data-theme="light"] .ce-popover__items::-webkit-scrollbar-thumb {
        background: rgba(15,23,42,0.16);
        border: 3px solid transparent;
        background-clip: padding-box;
    }

    /* Tema escuro: força texto branco no menu do + (Editor.js às vezes seta cor escura) */
    body:not([data-theme="light"]) .ce-popover,
    body:not([data-theme="light"]) .ce-popover * {
        color: #ffffff !important;
    }
    body:not([data-theme="light"]) .ce-popover svg,
    body:not([data-theme="light"]) .ce-popover svg * {
        fill: currentColor !important;
        stroke: currentColor !important;
        color: #ffffff !important;
    }
    body:not([data-theme="light"]) .ce-popover__item-description {
        color: rgba(255,255,255,0.72) !important;
    }

    /* Tema claro: garante que o popover não herde branco forçado */
    body[data-theme="light"] .ce-popover,
    body[data-theme="light"] .ce-popover * {
        color: var(--text-primary) !important;
    }
    body[data-theme="light"] .ce-popover__item-description {
        color: var(--text-secondary) !important;
    }

    .ce-popover__item-icon {
        background: rgba(255,255,255,0.08) !important;
        border: 1px solid rgba(255,255,255,0.10) !important;
        border-radius: 10px !important;
    }
    body:not([data-theme="light"]) .ce-popover__item-icon {
        background: rgba(0,0,0,0.35) !important;
        border: 1px solid rgba(255,255,255,0.12) !important;
    }
    body:not([data-theme="light"]) .ce-popover__item-icon svg,
    body:not([data-theme="light"]) .ce-popover__item-icon svg * {
        fill: currentColor !important;
        stroke: currentColor !important;
        color: #ffffff !important;
    }
    body[data-theme="light"] .ce-popover__item-icon {
        background: rgba(15,23,42,0.06) !important;
        border: 1px solid rgba(15,23,42,0.10) !important;
    }

    /* Botão + e engrenagem do Editor.js (estavam apagados) */
    .ce-toolbar__plus,
    .ce-toolbar__settings-btn {
        opacity: 1 !important;
        background: rgba(255,255,255,0.06) !important;
        border: 1px solid var(--border-subtle) !important;
        border-radius: 10px !important;
        color: var(--text-primary) !important;
    }
    .ce-toolbar__plus:hover,
    .ce-toolbar__settings-btn:hover {
        background: rgba(255,255,255,0.10) !important;
    }
    body[data-theme="light"] .ce-toolbar__plus,
    body[data-theme="light"] .ce-toolbar__settings-btn {
        background: rgba(15,23,42,0.06) !important;
    }
    body[data-theme="light"] .ce-toolbar__plus:hover,
    body[data-theme="light"] .ce-toolbar__settings-btn:hover {
        background: rgba(15,23,42,0.10) !important;
    }

    .notion-editor-wrap .ce-inline-tool,
    .notion-editor-wrap .ce-toolbox__button,
    .notion-editor-wrap .ce-popover__item,
    .notion-editor-wrap .ce-popover__item-icon,
    .notion-editor-wrap .ce-popover__item-label,
    .notion-editor-wrap .ce-conversion-tool {
        color: var(--text-primary) !important;
    }
    .notion-editor-wrap .ce-inline-tool,
    .notion-editor-wrap .ce-toolbox__button,
    .notion-editor-wrap .ce-popover__item,
    .notion-editor-wrap .ce-conversion-tool {
        border-radius: 10px !important;
    }
    .notion-editor-wrap .ce-inline-tool:hover,
    .notion-editor-wrap .ce-toolbox__button:hover,
    .notion-editor-wrap .ce-popover__item:hover,
    .notion-editor-wrap .ce-conversion-tool:hover {
        background: rgba(255,255,255,0.08) !important;
    }
    body[data-theme="light"] .notion-editor-wrap .ce-inline-tool:hover,
    body[data-theme="light"] .notion-editor-wrap .ce-toolbox__button:hover,
    body[data-theme="light"] .notion-editor-wrap .ce-popover__item:hover,
    body[data-theme="light"] .notion-editor-wrap .ce-conversion-tool:hover {
        background: rgba(15,23,42,0.06) !important;
    }

    .notion-editor-wrap .ce-inline-tool--active,
    .notion-editor-wrap .ce-toolbox__button--active,
    .notion-editor-wrap .ce-popover__item--active {
        background: rgba(229,57,53,0.16) !important;
        color: var(--text-primary) !important;
    }

    .notion-editor-wrap .ce-inline-tool svg,
    .notion-editor-wrap .ce-toolbox__button svg,
    .notion-editor-wrap .ce-popover__item-icon svg {
        fill: currentColor !important;
        color: var(--text-primary) !important;
    }

    .notion-editor-wrap .cdx-search-field {
        background: rgba(255,255,255,0.06) !important;
        border: 1px solid var(--border-subtle) !important;
        border-radius: 10px !important;
        color: var(--text-primary) !important;
    }
    .notion-editor-wrap .ce-popover,
    .notion-editor-wrap .ce-popover__container,
    .notion-editor-wrap .ce-popover__items {
        overflow: visible !important;
    }
    .notion-editor-wrap .ce-block,
    .notion-editor-wrap .ce-block__content,
    .notion-editor-wrap .ce-block__content * {
        overflow: visible;
    }
    body[data-theme="light"] .notion-editor-wrap .cdx-search-field {
        background: rgba(15,23,42,0.04) !important;
    }
    .notion-editor-wrap .cdx-search-field__input {
        color: var(--text-primary) !important;
    }
    .notion-editor-wrap .cdx-search-field__input::placeholder {
        color: var(--text-secondary) !important;
        opacity: 0.85;
    }

    .notion-editor-wrap .ce-popover__item-label,
    .notion-editor-wrap .ce-popover__item-description,
    .notion-editor-wrap .ce-conversion-tool__label,
    .notion-editor-wrap .ce-conversion-tool__description {
        color: var(--text-primary) !important;
    }
    .notion-editor-wrap .ce-popover__item-description,
    .notion-editor-wrap .ce-conversion-tool__description {
        color: var(--text-secondary) !important;
    }

    /* Conversion popover (Convert to) */
    .notion-editor-wrap .ce-conversion-toolbar__label,
    .notion-editor-wrap .ce-conversion-toolbar__label * {
        color: var(--text-secondary) !important;
    }
    .notion-editor-wrap .ce-conversion-tool__icon {
        background: rgba(255,255,255,0.08) !important;
        border: 1px solid rgba(255,255,255,0.10) !important;
        border-radius: 10px !important;
    }
    body[data-theme="light"] .notion-editor-wrap .ce-conversion-tool__icon {
        background: rgba(15,23,42,0.06) !important;
        border: 1px solid rgba(15,23,42,0.10) !important;
    }
    .notion-editor-wrap .ce-conversion-tool__icon svg {
        fill: currentColor !important;
        color: var(--text-primary) !important;
        opacity: 0.9;
    }
    .notion-editor-wrap .ce-conversion-tool {
        background: transparent !important;
    }
    .notion-editor-wrap .ce-conversion-tool:hover {
        background: rgba(255,255,255,0.08) !important;
    }
    body[data-theme="light"] .notion-editor-wrap .ce-conversion-tool:hover {
        background: rgba(15,23,42,0.06) !important;
    }

    .notion-editor-wrap .tuq-ce-action-btn {
        height: 28px;
        min-width: 28px;
        padding: 0 8px;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.06);
        color: var(--text-primary);
        font-size: 12px;
        line-height: 1;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        user-select: none;
    }
    .notion-editor-wrap .tuq-ce-action-btn:hover {
        background: rgba(255,255,255,0.10);
    }
    body[data-theme="light"] .notion-editor-wrap .tuq-ce-action-btn {
        border: 1px solid rgba(15,23,42,0.12);
        background: rgba(15,23,42,0.06);
    }
    body[data-theme="light"] .notion-editor-wrap .tuq-ce-action-btn:hover {
        background: rgba(15,23,42,0.10);
    }
    .notion-editor-wrap .tuq-ce-action-btn span {
        color: var(--text-secondary);
        font-size: 11px;
    }
    .notion-editor-hint {
        margin-top: 10px;
        font-size: 12px;
        color: var(--text-secondary);
        max-width: 900px;
        margin-left: auto;
        margin-right: auto;
    }

    .notion-sidebar a:hover {
        background: rgba(255,255,255,0.04) !important;
        border-color: rgba(255,255,255,0.07) !important;
    }
    body[data-theme="light"] .notion-sidebar a:hover {
        background: rgba(15,23,42,0.04) !important;
        border-color: rgba(15,23,42,0.08) !important;
    }

    /* Visual colors for blocks (MVP; persistência depois) */
    .notion-editor-wrap .tuq-block--c-gray { color: rgba(255,255,255,0.70) !important; }
    .notion-editor-wrap .tuq-block--c-red { color: #ff8a80 !important; }
    .notion-editor-wrap .tuq-block--c-yellow { color: #ffe082 !important; }
    .notion-editor-wrap .tuq-block--c-green { color: #a5d6a7 !important; }
    .notion-editor-wrap .tuq-block--c-blue { color: #90caf9 !important; }
    body[data-theme="light"] .notion-editor-wrap .tuq-block--c-gray { color: rgba(15,23,42,0.70) !important; }

    .notion-editor-wrap .tuq-block--bg-gray { background: rgba(255,255,255,0.04); border-radius: 10px; }
    .notion-editor-wrap .tuq-block--bg-brown { background: rgba(141,110,99,0.16); border-radius: 10px; }
    .notion-editor-wrap .tuq-block--bg-yellow { background: rgba(255,238,88,0.12); border-radius: 10px; }
    .notion-editor-wrap .tuq-block--bg-blue { background: rgba(66,165,245,0.12); border-radius: 10px; }
    body[data-theme="light"] .notion-editor-wrap .tuq-block--bg-gray { background: rgba(15,23,42,0.04); }

    /* Context menu */
    .tuq-ctx {
        position: fixed;
        z-index: 9999;
        width: 280px;
        border-radius: 12px;
        border: 1px solid var(--border-subtle);
        background: rgba(17,17,24,0.92);
        backdrop-filter: blur(10px);
        box-shadow: 0 18px 46px rgba(0,0,0,0.55);
        padding: 8px;
        max-height: min(520px, calc(100vh - 24px));
        overflow: visible;
        display: none;
    }
    body[data-theme="light"] .tuq-ctx {
        background: rgba(255,255,255,0.96);
        box-shadow: 0 18px 46px rgba(15,23,42,0.15);
    }
    .tuq-ctx .tuq-ctx-search {
        width: 100%;
        padding: 8px 10px;
        border-radius: 10px;
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        color: var(--text-primary);
        font-size: 13px;
        outline: none;
        margin-bottom: 8px;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .tuq-ctx .tuq-ctx-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 10px;
        cursor: pointer;
        color: var(--text-primary);
        font-size: 13px;
    }
    .tuq-ctx .tuq-ctx-item-left {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex: 1;
    }
    .tuq-ctx .tuq-ctx-swatch {
        width: 14px;
        height: 14px;
        border-radius: 4px;
        border: 1px solid rgba(255,255,255,0.18);
        background: rgba(255,255,255,0.16);
        flex: 0 0 14px;
    }
    body[data-theme="light"] .tuq-ctx .tuq-ctx-swatch {
        border: 1px solid rgba(15,23,42,0.18);
        background: rgba(15,23,42,0.10);
    }
    .tuq-ctx .tuq-ctx-swatch[data-swatch="t-gray"] { background: rgba(255,255,255,0.55); }
    .tuq-ctx .tuq-ctx-swatch[data-swatch="t-red"] { background: #ff8a80; }
    .tuq-ctx .tuq-ctx-swatch[data-swatch="t-yellow"] { background: #ffe082; }
    .tuq-ctx .tuq-ctx-swatch[data-swatch="t-green"] { background: #a5d6a7; }
    .tuq-ctx .tuq-ctx-swatch[data-swatch="t-blue"] { background: #90caf9; }
    body[data-theme="light"] .tuq-ctx .tuq-ctx-swatch[data-swatch="t-gray"] { background: rgba(15,23,42,0.40); }

    .tuq-ctx .tuq-ctx-swatch[data-swatch="bg-gray"] { background: rgba(255,255,255,0.12); }
    .tuq-ctx .tuq-ctx-swatch[data-swatch="bg-brown"] { background: rgba(141,110,99,0.34); }
    .tuq-ctx .tuq-ctx-swatch[data-swatch="bg-yellow"] { background: rgba(255,238,88,0.26); }
    .tuq-ctx .tuq-ctx-swatch[data-swatch="bg-blue"] { background: rgba(66,165,245,0.26); }
    body[data-theme="light"] .tuq-ctx .tuq-ctx-swatch[data-swatch="bg-gray"] { background: rgba(15,23,42,0.10); }

    .tuq-ctx .tuq-ctx-icon {
        width: 16px;
        height: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        line-height: 1;
        border-radius: 4px;
        border: 1px solid rgba(255,255,255,0.16);
        background: rgba(255,255,255,0.08);
        color: var(--text-secondary);
        flex: 0 0 16px;
    }
    body[data-theme="light"] .tuq-ctx .tuq-ctx-icon {
        border: 1px solid rgba(15,23,42,0.14);
        background: rgba(15,23,42,0.06);
    }
    .tuq-ctx .tuq-ctx-icon--mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 10px;
    }
    .tuq-ctx .tuq-ctx-item:hover {
        background: rgba(255,255,255,0.06);
    }
    body[data-theme="light"] .tuq-ctx .tuq-ctx-item:hover {
        background: rgba(15,23,42,0.06);
    }
    .tuq-ctx .tuq-ctx-item small {
        color: var(--text-secondary);
        font-size: 11px;
    }
    .tuq-ctx .tuq-ctx-sub {
        display: none;
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px solid var(--border-subtle);
    }

    .tuq-ctx .tuq-ctx-flyout {
        position: absolute;
        top: 44px;
        left: calc(100% + 10px);
        width: 320px;
        max-height: min(520px, calc(100vh - 24px));
        overflow: hidden;
        border-radius: 12px;
        border: 1px solid var(--border-subtle);
        background: rgba(17,17,24,0.94);
        backdrop-filter: blur(10px);
        box-shadow: 0 18px 46px rgba(0,0,0,0.55);
        padding: 8px;
        display: none;
    }
    body[data-theme="light"] .tuq-ctx .tuq-ctx-flyout {
        background: rgba(255,255,255,0.98);
        box-shadow: 0 18px 46px rgba(15,23,42,0.16);
    }
    .tuq-ctx .tuq-ctx-flyout .tuq-ctx-flyout-scroll {
        overflow-y: auto;
        max-height: calc(min(520px, calc(100vh - 24px)) - 16px);
        padding-right: 4px;
    }
    .tuq-ctx .tuq-ctx-flyout .tuq-ctx-flyout-scroll::-webkit-scrollbar { width: 10px; }
    .tuq-ctx .tuq-ctx-flyout .tuq-ctx-flyout-scroll::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.12);
        border-radius: 999px;
        border: 3px solid transparent;
        background-clip: padding-box;
    }
    body[data-theme="light"] .tuq-ctx .tuq-ctx-flyout .tuq-ctx-flyout-scroll::-webkit-scrollbar-thumb {
        background: rgba(15,23,42,0.16);
        border: 3px solid transparent;
        background-clip: padding-box;
    }

    .tuq-ctx .tuq-ctx-scroll {
        overflow-y: auto;
        max-height: calc(min(520px, calc(100vh - 24px)) - 54px);
        padding-right: 4px;
    }
    .tuq-ctx .tuq-ctx-scroll::-webkit-scrollbar { width: 10px; }
    .tuq-ctx .tuq-ctx-scroll::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.12);
        border-radius: 999px;
        border: 3px solid transparent;
        background-clip: padding-box;
    }
    body[data-theme="light"] .tuq-ctx .tuq-ctx-scroll::-webkit-scrollbar-thumb {
        background: rgba(15,23,42,0.16);
        border: 3px solid transparent;
        background-clip: padding-box;
    }

    /* Mobile */
    .notion-mobile-only { display: none; }
    #notion-sidebar-overlay { display: none; }

    @media (max-width: 920px) {
        .notion-shell {
            gap: 10px;
        }
        .notion-page-header {
            padding: 14px 14px 10px 14px;
        }
        .notion-page-body {
            padding: 12px 14px 18px 14px;
        }
        .notion-editor-wrap {
            max-width: 100%;
        }
        .notion-editor-wrap .ce-block__content,
        .notion-editor-wrap .ce-toolbar__content {
            max-width: 100%;
        }
        .notion-title {
            font-size: 24px;
        }
    }

    @media (max-width: 720px) {
        .notion-mobile-only { display: inline-flex; }

        /* No mobile, não mostrar botão desktop de colapsar (ele estava escondendo o + Nova) */
        .notion-sidebar-head .notion-toggle-sidebar { display: none !important; }

        .notion-shell {
            display: block;
            min-height: calc(100vh - 64px);
            position: relative;
        }

        .notion-sidebar {
            position: fixed;
            top: 64px;
            left: 0;
            height: calc(100vh - 64px);
            width: min(320px, 86vw);
            border-radius: 0 12px 12px 0;
            box-shadow: 0 18px 46px rgba(0,0,0,0.45);
            transform: translateX(-110%);
            transition: transform 0.18s ease-out;
            z-index: 100000;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .notion-shell--sidebar-open .notion-sidebar {
            transform: translateX(0);
        }

        #notion-sidebar-overlay {
            position: fixed;
            inset: 64px 0 0 0;
            background: rgba(0,0,0,0.45);
            z-index: 99999;
        }
        body[data-theme="light"] #notion-sidebar-overlay {
            background: rgba(15,23,42,0.32);
        }
        .notion-shell--sidebar-open #notion-sidebar-overlay {
            display: block;
        }

        .notion-page {
            width: 100%;
        }
        .notion-page-header {
            flex-direction: column;
            align-items: stretch;
            padding: 12px 12px 10px 12px;
        }
        .notion-title-wrap {
            width: 100%;
        }
        .notion-page-body {
            padding: 10px 12px 18px 12px;
        }
        .notion-emoji {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            border-radius: 10px;
        }
        .notion-title {
            font-size: 22px;
            padding: 4px 2px;
        }

        .notion-page-header > div:last-child {
            width: 100%;
            justify-content: flex-start !important;
            gap: 6px !important;
            flex-wrap: wrap !important;
        }

        .notion-page-header > div:last-child > button {
            flex: 0 0 auto;
        }

        /* Menus/contexto no mobile: não estourar lateral */
        .tuq-ctx {
            width: calc(100vw - 16px);
            left: 8px !important;
            right: 8px !important;
        }
        .tuq-ctx .tuq-ctx-flyout {
            left: 0;
            top: 44px;
            width: 100%;
        }

        /* Mobile: evita popover sair da tela, mas sem mudar posicionamento padrão */
        .ce-popover {
            max-height: calc(100vh - 96px) !important;
        }
        .ce-popover__items {
            max-height: calc(100vh - 150px) !important;
        }
    }
</style>

<div id="notion-sidebar-overlay"></div>

<div class="notion-shell">
    <div class="notion-sidebar">
        <div class="notion-sidebar-head">
            <div class="notion-sidebar-title">Caderno</div>
            <div style="display:flex; gap:8px; align-items:center; justify-content:flex-end;">
                <button type="button" class="notion-btn notion-toggle-sidebar" id="notion-toggle-sidebar" title="Minimizar painel">❮</button>
                <div style="position:relative;">
                <button type="button" id="btn-new-page" style="border:none; border-radius:10px; padding:6px 10px; background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; font-size:12px; cursor:pointer;">+ Nova</button>
                <div id="new-page-menu" style="
                    position:absolute;
                    right:0;
                    top:42px;
                    width:220px;
                    border-radius:12px;
                    border:1px solid var(--border-subtle);
                    background:var(--surface-card);
                    box-shadow: var(--shadow-card-strong);
                    padding:6px;
                    display:none;
                    z-index: 20;
                ">
                    <button type="button" class="new-page-menu-item" data-new-kind="page" style="
                        width:100%;
                        display:flex;
                        align-items:center;
                        justify-content:space-between;
                        gap:10px;
                        border:none;
                        background:transparent;
                        color:var(--text-primary);
                        padding:10px 10px;
                        border-radius:10px;
                        cursor:pointer;
                        font-size:13px;
                    ">
                        <span style="display:flex; align-items:center; gap:8px;"><span style="width:18px; text-align:center;">📄</span><span>Página</span></span>
                    </button>
                </div>
                </div>
            </div>
        </div>
        <div style="padding:8px; display:flex; flex-direction:column; gap:6px;">
            <?php if (empty($pages)): ?>
                <div style="padding:10px; color:var(--text-secondary); font-size:12px;">Você ainda não tem páginas. Clique em <b>+ Nova</b>.</div>
            <?php else: ?>
                <?php foreach ($pages as $p): ?>
                    <?php
                        $pid = (int)($p['id'] ?? 0);
                        $pParentId = (int)($p['parent_id'] ?? 0);
                        if ($pParentId > 0) { continue; }
                        $active = ($pid === $currentId) || ($activeRootId > 0 && $pid === $activeRootId);
                        $ptitle = (string)($p['title'] ?? 'Sem título');
                        $picon = trim((string)($p['icon'] ?? ''));
                        $depth = (int)($p['_depth'] ?? 0);
                        if ($depth < 0) { $depth = 0; }
                        if ($depth > 8) { $depth = 8; }
                        $padLeft = 10 + ($depth * 14);
                    ?>
                    <a href="/caderno?id=<?= $pid ?>" style="
                        display:flex; align-items:center; gap:8px;
                        padding:8px 10px; border-radius:10px;
                        padding-left: <?= (int)$padLeft ?>px;
                        text-decoration:none;
                        background:<?= $active ? 'rgba(229,57,53,0.14)' : 'transparent' ?>;
                        border:1px solid <?= $active ? 'rgba(229,57,53,0.25)' : 'transparent' ?>;
                        color:var(--text-primary);
                        font-size:13px;">
                        <span style="width:20px; text-align:center; opacity:0.9;"><?= $picon !== '' ? htmlspecialchars($picon) : '📄' ?></span>
                        <span style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($ptitle) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="notion-page">
        <div class="notion-page-header">
            <div class="notion-title-wrap">
                <button type="button" class="notion-btn notion-toggle-sidebar" id="notion-toggle-sidebar-alt" title="Mostrar/ocultar painel">☰</button>
                <?php if ($current): ?>
                    <div class="notion-emoji">
                        <input type="text" id="page-icon" value="<?= htmlspecialchars($currentIcon) ?>" placeholder="📄" style="
                            width:100%; height:100%; border:none; outline:none;
                            background:transparent; color:var(--text-primary); font-size:18px; text-align:center;">
                    </div>
                <?php endif; ?>
                <div style="min-width:0; flex:1;">
                    <?php if (!empty($breadcrumb) && $current): ?>
                        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <a href="/caderno" style="color:var(--text-secondary); text-decoration:none;">Caderno</a>
                            <?php foreach ($breadcrumb as $b): ?>
                                <?php $bid = (int)($b['id'] ?? 0); $btitle = (string)($b['title'] ?? ''); ?>
                                <?php if ($bid > 0 && $btitle !== '' && $bid !== (int)$currentId): ?>
                                    <span style="opacity:0.8;"> / </span>
                                    <a href="/caderno?id=<?= (int)$bid ?>" style="color:var(--text-secondary); text-decoration:none;"><?= htmlspecialchars($btitle) ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($current): ?>
                        <input type="text" id="page-title" value="<?= htmlspecialchars($currentTitle) ?>" placeholder="Sem título" class="notion-title">
                    <?php endif; ?>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                <button type="button" id="btn-mobile-pages" class="notion-mobile-only" style="
                    border:1px solid var(--border-subtle); border-radius:999px; padding:7px 12px;
                    background:var(--surface-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">
                    Páginas
                </button>
                <?php if ($current && $canEdit): ?>
                    <button type="button" id="btn-save" style="
                        border:1px solid var(--border-subtle); border-radius:999px; padding:7px 12px;
                        background:var(--surface-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">
                        Salvar
                    </button>
                <?php endif; ?>
                <?php if ($current && $isOwner): ?>
                    <button type="button" id="btn-publish" style="
                        border:1px solid var(--border-subtle); border-radius:999px; padding:7px 12px;
                        background:var(--surface-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">
                        <?= $isPublished ? 'Despublicar' : 'Publicar' ?>
                    </button>
                    <button type="button" id="btn-share" style="
                        border:1px solid var(--border-subtle); border-radius:999px; padding:7px 12px;
                        background:var(--surface-subtle); color:var(--text-primary); font-size:12px; cursor:pointer;">Compartilhar</button>
                    <button type="button" id="btn-delete" style="
                        border:1px solid rgba(229,57,53,0.35); border-radius:999px; padding:7px 12px;
                        background:rgba(229,57,53,0.10); color:var(--accent); font-size:12px; cursor:pointer;">Excluir</button>
                <?php endif; ?>
            </div>
        </div>

        <div id="emoji-picker-backdrop" class="tuq-emoji-backdrop" aria-hidden="true">
            <div class="tuq-emoji-modal" role="dialog" aria-modal="true" aria-label="Selecionar emoji">
                <div class="tuq-emoji-modal-header">
                    <div style="font-size:12px; font-weight:800; color:var(--text-primary);">Escolha um emoji</div>
                    <button type="button" id="emoji-picker-close" style="
                        border:1px solid var(--border-subtle);
                        background:transparent;
                        color:var(--text-primary);
                        border-radius:999px;
                        padding:6px 10px;
                        cursor:pointer;
                        font-size:12px;">Fechar</button>
                </div>
                <div style="padding:10px 12px 0 12px;">
                    <input type="text" id="emoji-picker-search" class="tuq-emoji-search" placeholder="Pesquisar (ex: fogo, check, pasta, ideia)" autocomplete="off">
                </div>
                <div id="emoji-picker-grid" class="tuq-emoji-grid"></div>
                <div class="tuq-emoji-hint">Dica: no Windows você também pode usar <b>Win + .</b> para abrir o painel de emojis.</div>
            </div>
        </div>

        <?php if ($current && $isOwner): ?>
            <div id="share-panel" style="display:none; padding:12px; border-bottom:1px solid var(--border-subtle);">
                <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
                    <div style="flex: 1 1 220px;">
                        <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Compartilhar com (e-mail)</label>
                        <input type="email" id="share-email" placeholder="email@exemplo.com" style="
                            width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle);
                            background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                    </div>
                    <div style="flex: 0 0 140px;">
                        <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Permissão</label>
                        <select id="share-role" style="
                            width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle);
                            background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                            <option value="view">Somente ver</option>
                            <option value="edit">Pode editar</option>
                        </select>
                    </div>
                    <button type="button" id="btn-share-add" style="
                        border:none; border-radius:10px; padding:9px 14px;
                        background:linear-gradient(135deg,#e53935,#ff6f60); color:#050509; font-weight:700; font-size:12px; cursor:pointer;">Adicionar</button>
                </div>

                <div style="margin-top:10px;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:6px;">Pessoas com acesso</div>
                    <div id="share-list" style="display:flex; flex-direction:column; gap:6px;">
                        <?php if (empty($shares)): ?>
                            <div style="font-size:12px; color:var(--text-secondary);">Ninguém ainda.</div>
                        <?php else: ?>
                            <?php foreach ($shares as $s): ?>
                                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; border:1px solid var(--border-subtle); background:var(--surface-subtle); padding:8px 10px; border-radius:10px;">
                                    <div style="min-width:0;">
                                        <div style="font-size:13px; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars((string)($s['email'] ?? '')) ?></div>
                                        <div style="font-size:11px; color:var(--text-secondary);"><?= htmlspecialchars((string)($s['role'] ?? 'view')) ?></div>
                                    </div>
                                    <button type="button" class="btn-share-remove" data-user-id="<?= (int)($s['user_id'] ?? 0) ?>" style="
                                        border:1px solid var(--border-subtle); border-radius:999px; padding:6px 10px;
                                        background:transparent; color:var(--text-primary); font-size:12px; cursor:pointer;">Remover</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($current && $isPublished && $publicUrl !== ''): ?>
                    <?php
                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
                        $absPublicUrl = $host !== '' ? ($scheme . '://' . $host . $publicUrl) : $publicUrl;
                    ?>
                    <div style="margin-top:10px; border-top:1px dashed var(--border-subtle); padding-top:10px;">
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Link público (somente leitura)</div>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input type="text" id="public-link" readonly value="<?= htmlspecialchars($absPublicUrl) ?>" style="
                                flex:1 1 auto; width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--border-subtle);
                                background:var(--surface-subtle); color:var(--text-primary); font-size:13px;">
                            <button type="button" id="btn-copy-public-link" style="
                                border:1px solid var(--border-subtle); border-radius:10px; padding:9px 12px;
                                background:transparent; color:var(--text-primary); font-size:12px; cursor:pointer; white-space:nowrap;">Copiar link</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="notion-page-body">
            <div class="notion-editor-wrap">
                <?php if ($current): ?>
                    <div id="editorjs" style="background:transparent;"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="notion-preview-modal" id="notion-preview-modal" aria-hidden="true">
    <div class="notion-preview-backdrop" id="notion-preview-backdrop"></div>
    <div class="notion-preview-card" role="dialog" aria-modal="true" aria-label="Pré-visualização">
        <div class="notion-preview-head">
            <div class="notion-preview-title" id="notion-preview-title">Arquivo</div>
            <div style="display:flex; gap:8px; align-items:center;">
                <a class="notion-btn" id="notion-preview-download" href="#" download style="display:none;">Baixar</a>
                <button type="button" class="notion-btn" id="notion-preview-close">Fechar</button>
            </div>
        </div>
        <div class="notion-preview-body" id="notion-preview-body"></div>
    </div>
</div>

<div class="tuq-ctx" id="tuq-ctx">
    <input class="tuq-ctx-search" id="tuq-ctx-search" type="text" placeholder="Pesquisar ações..." />
    <div class="tuq-ctx-scroll" id="tuq-ctx-scroll">
        <div class="tuq-ctx-item" data-action="toggle-transform">
            <span>Transformar em</span>
            <small>›</small>
        </div>
        <div class="tuq-ctx-item" data-action="toggle-color">
            <span>Cor</span>
            <small>›</small>
        </div>
        <?php if ($current && $canEdit): ?>
            <div class="tuq-ctx-item" data-action="create-subpage">
                <span class="tuq-ctx-item-left"><span class="tuq-ctx-icon">↳</span><span>Subpágina</span></span>
                <small>Criar</small>
            </div>
        <?php endif; ?>
        <div class="tuq-ctx-item" data-action="insert-image">
            <span class="tuq-ctx-item-left"><span class="tuq-ctx-icon">🖼</span><span>Imagem</span></span>
            <small>Upload</small>
        </div>
        <div class="tuq-ctx-item" data-action="insert-file">
            <span class="tuq-ctx-item-left"><span class="tuq-ctx-icon">📎</span><span>Arquivo</span></span>
            <small>Upload</small>
        </div>
        <div class="tuq-ctx-item" data-action="duplicate">
            <span>Duplicar</span>
            <small>Ctrl+D</small>
        </div>
        <div class="tuq-ctx-item" data-action="delete">
            <span>Excluir</span>
            <small>Del</small>
        </div>

    </div>

    <div class="tuq-ctx-flyout" id="tuq-ctx-flyout-transform">
        <div class="tuq-ctx-flyout-scroll">
            <div class="tuq-ctx-item" data-action="transform" data-to="header" data-level="1"><span class="tuq-ctx-item-left"><span class="tuq-ctx-icon">H1</span><span>Título 1</span></span></div>
            <div class="tuq-ctx-item" data-action="transform" data-to="header" data-level="2"><span class="tuq-ctx-item-left"><span class="tuq-ctx-icon">H2</span><span>Título 2</span></span></div>
            <div class="tuq-ctx-item" data-action="transform" data-to="header" data-level="3"><span class="tuq-ctx-item-left"><span class="tuq-ctx-icon">H3</span><span>Título 3</span></span></div>
            <div class="tuq-ctx-item" data-action="transform" data-to="list" data-style="unordered"><span class="tuq-ctx-item-left"><span class="tuq-ctx-icon">•</span><span>Lista com marcadores</span></span></div>
            <div class="tuq-ctx-item" data-action="transform" data-to="list" data-style="ordered"><span class="tuq-ctx-item-left"><span class="tuq-ctx-icon">1.</span><span>Lista numerada</span></span></div>
            <div class="tuq-ctx-item" data-action="transform" data-to="checklist"><span class="tuq-ctx-item-left"><span class="tuq-ctx-icon">☑</span><span>Lista de tarefas</span></span></div>
            <div class="tuq-ctx-item" data-action="transform" data-to="quote"><span class="tuq-ctx-item-left"><span class="tuq-ctx-icon">❝</span><span>Citação</span></span></div>
            <div class="tuq-ctx-item" data-action="transform" data-to="code"><span class="tuq-ctx-item-left"><span class="tuq-ctx-icon tuq-ctx-icon--mono">&lt;/&gt;</span><span>Código</span></span></div>
        </div>
    </div>

    <div class="tuq-ctx-flyout" id="tuq-ctx-flyout-color">
        <div class="tuq-ctx-flyout-scroll">
            <div style="font-size:12px; color:var(--text-secondary); padding: 6px 10px 4px 10px;">Cor do texto</div>
            <div class="tuq-ctx-item" data-action="color" data-kind="text" data-value="gray"><span class="tuq-ctx-item-left"><span class="tuq-ctx-swatch" data-swatch="t-gray"></span><span>Texto cinza</span></span></div>
            <div class="tuq-ctx-item" data-action="color" data-kind="text" data-value="red"><span class="tuq-ctx-item-left"><span class="tuq-ctx-swatch" data-swatch="t-red"></span><span>Texto vermelho</span></span></div>
            <div class="tuq-ctx-item" data-action="color" data-kind="text" data-value="yellow"><span class="tuq-ctx-item-left"><span class="tuq-ctx-swatch" data-swatch="t-yellow"></span><span>Texto amarelo</span></span></div>
            <div class="tuq-ctx-item" data-action="color" data-kind="text" data-value="green"><span class="tuq-ctx-item-left"><span class="tuq-ctx-swatch" data-swatch="t-green"></span><span>Texto verde</span></span></div>
            <div class="tuq-ctx-item" data-action="color" data-kind="text" data-value="blue"><span class="tuq-ctx-item-left"><span class="tuq-ctx-swatch" data-swatch="t-blue"></span><span>Texto azul</span></span></div>

            <div style="font-size:12px; color:var(--text-secondary); padding: 10px 10px 4px 10px;">Cor de fundo</div>
            <div class="tuq-ctx-item" data-action="color" data-kind="bg" data-value="gray"><span class="tuq-ctx-item-left"><span class="tuq-ctx-swatch" data-swatch="bg-gray"></span><span>Fundo cinza</span></span></div>
            <div class="tuq-ctx-item" data-action="color" data-kind="bg" data-value="brown"><span class="tuq-ctx-item-left"><span class="tuq-ctx-swatch" data-swatch="bg-brown"></span><span>Fundo marrom</span></span></div>
            <div class="tuq-ctx-item" data-action="color" data-kind="bg" data-value="yellow"><span class="tuq-ctx-item-left"><span class="tuq-ctx-swatch" data-swatch="bg-yellow"></span><span>Fundo amarelo</span></span></div>
            <div class="tuq-ctx-item" data-action="color" data-kind="bg" data-value="blue"><span class="tuq-ctx-item-left"><span class="tuq-ctx-swatch" data-swatch="bg-blue"></span><span>Fundo azul</span></span></div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/@editorjs/editorjs@2.28.2"></script>
<script src="https://unpkg.com/@editorjs/header@2.8.1/dist/header.umd.js"></script>
<script src="https://unpkg.com/@editorjs/list@1.9.0/dist/list.umd.js"></script>
<script src="https://unpkg.com/@editorjs/checklist@1.6.0/dist/checklist.umd.js"></script>
<script src="https://unpkg.com/@editorjs/quote@2.5.0/dist/bundle.js"></script>
<script src="https://unpkg.com/@editorjs/code@2.8.0/dist/bundle.js"></script>
<script src="https://unpkg.com/@editorjs/image@2.10.1/dist/image.umd.js"></script>
<script src="https://unpkg.com/@editorjs/attaches@1.3.0/dist/bundle.js"></script>
<script src="https://unpkg.com/editorjs-undo@2.0.0/dist/bundle.js"></script>
<script>
(function () {
    var pageId = <?= (int)$currentId ?>;
    var canEdit = <?= $canEdit ? 'true' : 'false' ?>;
    var isOwner = <?= $isOwner ? 'true' : 'false' ?>;
    var initialJson = <?= json_encode($contentJson !== '' ? $contentJson : '') ?>;
    var pagesById = <?= json_encode($pagesById) ?>;

    var $ = function (id) { return document.getElementById(id); };

    document.addEventListener('dragstart', function (e) {
        try {
            var t = e && e.target ? e.target : null;
            if (t && t.closest && (t.closest('.tuq-subpage-card') || t.closest('.tuq-subpage-inline'))) {
                e.preventDefault();
            }
        } catch (err) {}
    }, true);

    document.addEventListener('drop', function (e) {
        try {
            var t = e && e.target ? e.target : null;
            if (t && t.closest && (t.closest('.tuq-subpage-card') || t.closest('.tuq-subpage-inline'))) {
                e.preventDefault();
            }
        } catch (err) {}
    }, true);

    document.addEventListener('click', function (e) {
        try {
            var t = e && e.target ? e.target : null;
            var a = t && t.closest ? t.closest('a.tuq-subpage-inline') : null;
            if (!a) return;
            var href = a.getAttribute('href');
            if (!href) return;
            e.preventDefault();
            window.location.href = href;
        } catch (err) {}
    }, true);

    (function () {
        var shell = document.querySelector('.notion-shell');
        var btn = $('btn-mobile-pages');
        var overlay = $('notion-sidebar-overlay');
        if (!shell || !btn || !overlay) return;

        function openSidebar() {
            shell.classList.add('notion-shell--sidebar-open');
        }
        function closeSidebar() {
            shell.classList.remove('notion-shell--sidebar-open');
        }
        function toggleSidebar() {
            if (shell.classList.contains('notion-shell--sidebar-open')) closeSidebar();
            else openSidebar();
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            toggleSidebar();
        });
        overlay.addEventListener('click', function () {
            closeSidebar();
        });

        // Ao clicar em uma página no mobile, fecha o drawer
        var sidebar = document.querySelector('.notion-sidebar');
        if (sidebar) {
            sidebar.addEventListener('click', function (e) {
                var a = e.target && e.target.closest ? e.target.closest('a[href^="/caderno"]') : null;
                if (a) closeSidebar();
            });
        }
    })();

    function openPreviewModal(url, name, mime) {
        var modal = $('notion-preview-modal');
        var backdrop = $('notion-preview-backdrop');
        var closeBtn = $('notion-preview-close');
        var titleEl = $('notion-preview-title');
        var bodyEl = $('notion-preview-body');
        var downloadEl = $('notion-preview-download');
        if (!modal || !backdrop || !closeBtn || !titleEl || !bodyEl || !downloadEl) return;

        url = String(url || '');
        name = String(name || 'Arquivo');
        mime = String(mime || '');
        if (!url) return;

        titleEl.textContent = name || 'Arquivo';
        bodyEl.innerHTML = '';

        downloadEl.style.display = 'inline-flex';
        try { downloadEl.removeAttribute('download'); } catch (e) {}
        downloadEl.href = '/caderno/midia/download?page_id=' + encodeURIComponent(String(pageId || ''))
            + '&url=' + encodeURIComponent(url)
            + '&name=' + encodeURIComponent(name || 'arquivo');

        var lowerMime = mime.toLowerCase();
        var lowerUrl = url.toLowerCase();
        var isImage = (lowerMime.indexOf('image/') === 0) || /\.(png|jpe?g|gif|webp|bmp|svg)$/i.test(lowerUrl);
        var isPdf = (lowerMime.indexOf('application/pdf') === 0) || /\.pdf($|\?)/i.test(lowerUrl);

        if (isImage) {
            var img = document.createElement('img');
            img.src = url;
            img.alt = name || 'Imagem';
            bodyEl.appendChild(img);
        } else if (isPdf) {
            var frame = document.createElement('iframe');
            frame.className = 'notion-preview-frame';
            frame.src = url;
            bodyEl.appendChild(frame);
        } else {
            bodyEl.innerHTML = '<div style="padding:14px; color:var(--text-secondary); font-size:13px;">Pré-visualização indisponível para este tipo de arquivo. Use <b>Baixar</b>.</div>';
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');

        function close() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            bodyEl.innerHTML = '';
        }

        backdrop.onclick = close;
        closeBtn.onclick = close;
        document.addEventListener('keydown', function escClose(e) {
            if (!modal.classList.contains('is-open')) return;
            if (e && e.key === 'Escape') {
                close();
                document.removeEventListener('keydown', escClose);
            }
        });
    }

    document.addEventListener('click', function (e) {
        try {
            var t = e && e.target ? e.target : null;
            if (!t) return;

            var inEditor = t.closest && t.closest('.notion-editor-wrap');
            if (!inEditor) return;

            // AttachesTool: botão/link de download normalmente é um <a>
            var attBtn = t.closest ? t.closest('.cdx-attaches__download-button') : null;
            if (attBtn && attBtn.getAttribute) {
                var href = attBtn.getAttribute('href');
                if (!href) return;
                e.preventDefault();
                var titleEl = attBtn.closest('.cdx-attaches') ? attBtn.closest('.cdx-attaches').querySelector('.cdx-attaches__title') : null;
                var fname = titleEl ? String(titleEl.textContent || '').trim() : 'Arquivo';
                openPreviewModal(href, fname, '');
                return;
            }

            // ImageTool: se clicar na imagem, abre modal
            var img = t.closest ? t.closest('.image-tool__image-picture img') : null;
            if (img && img.getAttribute) {
                var src = img.getAttribute('src');
                if (!src) return;
                e.preventDefault();
                openPreviewModal(src, 'Imagem', 'image/*');
                return;
            }

            // Fallback: qualquer link dentro do editor que estaria abrindo nova guia
            var a = t.closest ? t.closest('a[href]') : null;
            if (a && a.getAttribute) {
                var ah = a.getAttribute('href');
                var target = (a.getAttribute('target') || '').toLowerCase();
                if (ah && (target === '_blank')) {
                    e.preventDefault();
                    openPreviewModal(ah, 'Arquivo', '');
                    return;
                }
            }
        } catch (err) {}
    }, true);

    (function () {
        var SIDEBAR_KEY = 'caderno.sidebarCollapsed';

        function setSidebarCollapsed(collapsed) {
            if (collapsed) {
                document.body.classList.add('notion-sidebar-collapsed');
            } else {
                document.body.classList.remove('notion-sidebar-collapsed');
            }
            try {
                localStorage.setItem(SIDEBAR_KEY, collapsed ? '1' : '0');
            } catch (e) {}

            var btn = $('notion-toggle-sidebar');
            if (btn) {
                btn.textContent = collapsed ? '❯' : '❮';
                btn.title = collapsed ? 'Expandir painel' : 'Minimizar painel';
            }
        }

        function getSidebarCollapsed() {
            try {
                return localStorage.getItem(SIDEBAR_KEY) === '1';
            } catch (e) {
                return false;
            }
        }

        setSidebarCollapsed(getSidebarCollapsed());

        var toggleBtn = $('notion-toggle-sidebar');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                setSidebarCollapsed(!document.body.classList.contains('notion-sidebar-collapsed'));
            });
        }

        var toggleBtnAlt = $('notion-toggle-sidebar-alt');
        if (toggleBtnAlt) {
            toggleBtnAlt.addEventListener('click', function () {
                setSidebarCollapsed(!document.body.classList.contains('notion-sidebar-collapsed'));
            });
        }
    })();

    function postForm(url, data) {
        var fd = new FormData();
        Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
        return fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) {
                var ct = (r.headers && r.headers.get) ? (r.headers.get('content-type') || '') : '';
                if (ct.toLowerCase().indexOf('application/json') >= 0) {
                    return r.json().then(function (j) {
                        return { ok: r.ok, status: r.status, json: j, text: null };
                    });
                }
                return r.text().then(function (t) {
                    return { ok: r.ok, status: r.status, json: null, text: t || '' };
                });
            })
            .catch(function (err) {
                return { ok: false, status: 0, json: null, text: String(err || 'Erro') };
            });
    }

    function showActionError(res, fallback) {
        var msg = fallback || 'Falha ao executar ação.';
        if (res && res.json && res.json.error) msg = String(res.json.error);
        else if (res && res.status === 403) msg = 'Sem permissão ou sem assinatura ativa.';
        else if (res && res.status === 401) msg = 'Você precisa estar logado.';
        else if (res && res.status && res.status >= 500) msg = 'Erro no servidor.';
        setHint(msg);
        try { alert(msg); } catch (e) {}
    }

    function safeJsonParse(text) {
        try { return JSON.parse(text); } catch (e) { return null; }
    }

    var editorData = null;
    if (initialJson && typeof initialJson === 'string') {
        editorData = safeJsonParse(initialJson);
    }

    if (!editorData) {
        editorData = { time: Date.now(), blocks: [] };
    }

    try {
        if (editorData && editorData.blocks && pagesById) {
            for (var bi = 0; bi < editorData.blocks.length; bi++) {
                var b = editorData.blocks[bi];
                if (!b || b.type !== 'subpage') continue;
                var d = b.data || {};
                var sid = (d.id !== null && typeof d.id !== 'undefined') ? String(d.id) : '';
                if (!sid || !pagesById[sid]) continue;
                var p = pagesById[sid] || {};
                if (typeof p.title === 'string' && p.title !== '') d.title = p.title;
                if (typeof p.icon === 'string') d.icon = p.icon;
                b.data = d;
            }
        }
    } catch (e) {}

    function getMissingEditorTools() {
        var missing = [];
        if (typeof EditorJS === 'undefined') missing.push('EditorJS');
        if (typeof Header === 'undefined') missing.push('Header');
        if (typeof List === 'undefined') missing.push('List');
        if (typeof Checklist === 'undefined') missing.push('Checklist');
        if (typeof Quote === 'undefined') missing.push('Quote');
        if (typeof CodeTool === 'undefined') missing.push('CodeTool');
        if (typeof ImageTool === 'undefined') missing.push('ImageTool');
        if (typeof AttachesTool === 'undefined') missing.push('AttachesTool');
        return missing;
    }

    function uploadMediaFile(file) {
        var fd = new FormData();
        fd.append('page_id', String(pageId));
        fd.append('file', file);
        return fetch('/caderno/midia/upload', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.json().then(function (j) {
                return { ok: res.ok, status: res.status, json: j };
            });
        }).catch(function (e) {
            return { ok: false, status: 0, json: { success: 0, message: String(e || 'Erro') } };
        });
    }

    function insertImageByUrl(url, atIndex) {
        if (!editor || !url) return;
        try {
            var at = (typeof atIndex === 'number' && atIndex >= 0) ? atIndex : undefined;
            if (typeof at === 'undefined') {
                var idx = (editor.blocks && typeof editor.blocks.getCurrentBlockIndex === 'function') ? editor.blocks.getCurrentBlockIndex() : null;
                at = (typeof idx === 'number' && idx >= 0) ? idx + 1 : undefined;
            }
            editor.blocks.insert('image', { file: { url: url }, caption: '' }, {}, at, true);
            debounceSave();
        } catch (e) {}
    }

    function insertFileByMeta(fileMeta, atIndex) {
        if (!editor || !fileMeta) return;
        try {
            if (!fileMeta.url) {
                setHint('Falha ao inserir arquivo: URL inválida.');
                try { console.error('insertFileByMeta missing url', fileMeta); } catch (e) {}
                return;
            }
            var at = (typeof atIndex === 'number' && atIndex >= 0) ? atIndex : undefined;
            if (typeof at === 'undefined') {
                var idx = (editor.blocks && typeof editor.blocks.getCurrentBlockIndex === 'function') ? editor.blocks.getCurrentBlockIndex() : null;
                at = (typeof idx === 'number' && idx >= 0) ? idx + 1 : undefined;
            }
            editor.blocks.insert('attaches', { file: fileMeta, title: fileMeta.title || fileMeta.name || '' }, {}, at, true);
            debounceSave();
        } catch (e) {
            setHint('Não foi possível inserir o arquivo no editor.');
            try { console.error('insertFileByMeta failed', e, fileMeta); } catch (err) {}
        }
    }

    function pickFile(accept) {
        return new Promise(function (resolve) {
            var input = document.createElement('input');
            input.type = 'file';
            if (accept) input.accept = accept;
            input.style.display = 'none';
            document.body.appendChild(input);
            input.addEventListener('change', function () {
                var f = input.files && input.files[0] ? input.files[0] : null;
                try { document.body.removeChild(input); } catch (e) {}
                resolve(f);
            });
            input.click();
        });
    }

    var editorInitError = false;
    var editor = null;
    if (pageId) {
        var missingTools = getMissingEditorTools();
        if (missingTools.length) {
            editorInitError = true;
            setHint('Erro ao carregar editor: ' + missingTools.join(', ') + '. Recarregue a página.');
            try { console.error('Editor tools missing:', missingTools); } catch (e) {}
        } else {
            try {
                function esc(s) {
                    return String(s || '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                }

                function SubpageTool(opts) {
                    opts = opts || {};
                    this.api = opts.api;
                    this.data = opts.data || {};
                    this.readOnly = !!opts.readOnly;
                }
                SubpageTool.toolbox = {
                    title: 'Subpágina',
                    icon: '↳'
                };
                SubpageTool.isReadOnlySupported = true;
                SubpageTool.prototype.render = function () {
                    var d = this.data || {};
                    var id = String(d.id || '');
                    var title = String(d.title || 'Sem título');
                    var icon = String(d.icon || '📄');
                    var href = id ? ('/caderno?id=' + encodeURIComponent(id)) : '#';

                    var wrap = document.createElement('div');
                    wrap.style.width = '100%';
                    wrap.style.margin = '6px 0';
                    try { wrap.contentEditable = 'false'; } catch (e) {}

                    var a = document.createElement('a');
                    a.className = 'tuq-subpage-inline';
                    a.setAttribute('href', href);
                    a.setAttribute('draggable', 'false');
                    a.style.cssText = 'display:flex; align-items:center; gap:10px; width:100%; padding:12px 12px; border-radius:12px; border:1px solid var(--border-subtle); background: var(--surface-subtle); color: var(--text-primary); text-decoration:none;';

                    var iconBox = document.createElement('div');
                    iconBox.style.cssText = 'width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:rgba(229,57,53,0.10); border:1px solid rgba(229,57,53,0.20);';
                    iconBox.innerHTML = '<span style="font-size:16px;">' + esc(icon) + '</span>';

                    var text = document.createElement('div');
                    text.style.cssText = 'min-width:0; flex:1;';
                    text.innerHTML = '<div style="font-size:13px; font-weight:800; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + esc(title) + '</div><div style="font-size:11px; color:var(--text-secondary);">Subpágina</div>';

                    var chevron = document.createElement('div');
                    chevron.style.cssText = 'font-size:12px; color:var(--text-secondary); opacity:0.9;';
                    chevron.textContent = '›';

                    a.appendChild(iconBox);
                    a.appendChild(text);
                    a.appendChild(chevron);
                    wrap.appendChild(a);
                    return wrap;
                };
                SubpageTool.prototype.save = function () {
                    var d = this.data || {};
                    return {
                        id: d.id ? String(d.id) : '',
                        title: d.title ? String(d.title) : 'Sem título',
                        icon: d.icon ? String(d.icon) : '📄'
                    };
                };

                editor = new EditorJS({
                    holder: 'editorjs',
                    readOnly: !canEdit,
                    data: editorData,
                    autofocus: false,
                    onReady: function () {
                        try {
                            if (canEdit && typeof Undo !== 'undefined') {
                                new Undo({ editor: editor });
                            }
                        } catch (e) {}
                    },
                    tools: {
                        subpage: { class: SubpageTool },
                        header: { class: Header, inlineToolbar: true, config: { levels: [1,2,3], defaultLevel: 2 } },
                        list: { class: List, inlineToolbar: true },
                        checklist: { class: Checklist, inlineToolbar: true },
                        quote: { class: Quote, inlineToolbar: true },
                        code: { class: CodeTool },
                        image: {
                            class: ImageTool,
                            inlineToolbar: true,
                            config: {
                                field: 'file',
                                endpoints: { byFile: '/caderno/midia/upload', byUrl: '/caderno/midia/upload' },
                                additionalRequestData: { page_id: String(pageId) },
                                captionPlaceholder: 'Legenda',
                                buttonContent: 'Enviar imagem'
                            }
                        },
                        attaches: {
                            class: AttachesTool,
                            config: {
                                endpoint: '/caderno/midia/upload',
                                field: 'file',
                                types: '*/*',
                                buttonText: 'Enviar arquivo',
                                errorMessage: 'Falha ao enviar arquivo',
                                additionalRequestHeaders: { 'X-Requested-With': 'XMLHttpRequest' },
                                additionalRequestData: { page_id: String(pageId) }
                            }
                        }
                    }
                });

                try {
                    if (editor && editor.isReady && typeof editor.isReady.then === 'function') {
                        editor.isReady.catch(function (e) {
                            editorInitError = true;
                            setHint('Erro ao iniciar editor. Recarregue a página.');
                            try { console.error('Editor isReady failed:', e); } catch (err) {}
                        });
                    }
                } catch (e) {}
            } catch (e) {
                editorInitError = true;
                setHint('Erro ao iniciar editor. Recarregue a página.');
                try { console.error('Editor init error:', e); } catch (err) {}
            }
        }
    }

    var saving = false;
    var pending = false;
    var lastSaved = 0;

    function setHint(text) {
        var el = $('editor-hint');
        if (el) el.textContent = text || '';
    }

    function scheduleSave() {
        if (!pageId || !canEdit) return;
        if (saving) { pending = true; return; }
        saving = true;
        setHint('Salvando...');

        var manualSave = false;
        try { manualSave = !!(btnSave && btnSave.getAttribute && btnSave.getAttribute('data-manual-save') === '1'); } catch (e) { manualSave = false; }
        var prevSaveLabel = null;
        function setSaveBtnState(state) {
            if (!btnSave || !manualSave) return;
            try {
                if (!prevSaveLabel) prevSaveLabel = btnSave.textContent || 'Salvar';
                if (state === 'saving') {
                    btnSave.disabled = true;
                    btnSave.textContent = 'Salvando…';
                    return;
                }
                if (state === 'ok') {
                    btnSave.disabled = false;
                    btnSave.textContent = 'Salvo!';
                } else if (state === 'error') {
                    btnSave.disabled = false;
                    btnSave.textContent = 'Falhou';
                }
                setTimeout(function () {
                    try {
                        btnSave.textContent = prevSaveLabel || 'Salvar';
                        btnSave.disabled = false;
                        btnSave.setAttribute('data-manual-save', '0');
                    } catch (e2) {}
                }, 2000);
            } catch (e) {}
        }

        setSaveBtnState('saving');
        if (!editor || typeof editor.save !== 'function') {
            saving = false;
            return;
        }

        var ready = (editor && editor.isReady && typeof editor.isReady.then === 'function') ? editor.isReady : Promise.resolve();
        ready.then(function () {
            if (!editor || typeof editor.save !== 'function') {
                throw new Error('Editor not ready');
            }
            return editor.save();
        }).then(function (data) {
            return postForm('/caderno/salvar', {
                page_id: String(pageId),
                content_json: JSON.stringify(data)
            });
        }).then(function (res) {
            saving = false;
            lastSaved = Date.now();
            if (!res.json || res.json.ok !== true) {
                setHint((res.json && res.json.error) ? res.json.error : 'Falha ao salvar.');
                setSaveBtnState('error');
                return;
            }
            setHint('Salvo agora');
            setSaveBtnState('ok');
            if (pending) { pending = false; setTimeout(scheduleSave, 250); }
        }).catch(function () {
            saving = false;
            setHint('Falha ao salvar.');
            setSaveBtnState('error');
        });
    }

    var debounceTimer = null;
    function debounceSave() {
        if (!pageId || !canEdit) return;
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(scheduleSave, 650);
    }

    if (!editorInitError && canEdit && editor) {
        document.addEventListener('keyup', function (e) {
            if (!e) return;
            debounceSave();
        }, true);
        document.addEventListener('mouseup', function () { debounceSave(); }, true);
    } else {
        if (!editorInitError && pageId && !canEdit) {
            setHint('Somente leitura (sem permissão de edição).');
        }
    }

    var btnSave = $('btn-save');
    if (btnSave && !editorInitError && canEdit && pageId) {
        btnSave.addEventListener('click', function () {
            try { btnSave.setAttribute('data-manual-save', '1'); } catch (e) {}
            if (saving) {
                pending = true;
                try {
                    btnSave.disabled = true;
                    btnSave.textContent = 'Salvando…';
                } catch (e2) {}
                return;
            }
            scheduleSave();
        });
    }

    var btnCopyPublic = $('btn-copy-public-link');
    var publicLinkInput = $('public-link');
    if (btnCopyPublic && publicLinkInput) {
        btnCopyPublic.addEventListener('click', function () {
            if (btnCopyPublic.getAttribute('data-copying') === '1') return;
            var text = String(publicLinkInput.value || '');
            if (!text) return;

            var prevLabel = btnCopyPublic.textContent;
            function setBtnState(ok) {
                try {
                    btnCopyPublic.setAttribute('data-copying', '1');
                    btnCopyPublic.disabled = true;
                    btnCopyPublic.textContent = ok ? 'Copiado!' : 'Falhou';
                    setTimeout(function () {
                        try {
                            btnCopyPublic.disabled = false;
                            btnCopyPublic.setAttribute('data-copying', '0');
                            btnCopyPublic.textContent = prevLabel || 'Copiar link';
                        } catch (e2) {}
                    }, 2000);
                } catch (e) {}
            }

            if (navigator && navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    setHint('Link copiado');
                    setBtnState(true);
                }).catch(function () {
                    try {
                        publicLinkInput.focus();
                        publicLinkInput.select();
                        document.execCommand('copy');
                        setHint('Link copiado');
                        setBtnState(true);
                    } catch (e) {}
                });
                return;
            }
            try {
                publicLinkInput.focus();
                publicLinkInput.select();
                document.execCommand('copy');
                setHint('Link copiado');
                setBtnState(true);
            } catch (e) {}
        });
    }

    // Context menu (MVP): aplica ações no bloco atual
    var ctx = $('tuq-ctx');
    var ctxSearch = $('tuq-ctx-search');
    var subTransform = $('tuq-ctx-flyout-transform');
    var subColor = $('tuq-ctx-flyout-color');
    var currentBlockIndex = null;
    var lastSelectionRange = null;
    var lastSelectionBlockIndex = null;

    function hideCtx() {
        if (!ctx) return;
        ctx.style.display = 'none';
        if (subTransform) subTransform.style.display = 'none';
        if (subColor) subColor.style.display = 'none';
        if (ctxSearch) ctxSearch.value = '';
        currentBlockIndex = null;
    }

    function positionFlyout(flyout) {
        if (!ctx || !flyout) return;
        try {
            var ctxRect = ctx.getBoundingClientRect();
            var w = flyout.offsetWidth || 320;
            var gap = 10;
            var openRight = (ctxRect.right + gap + w) <= (window.innerWidth || 1200);
            if (openRight) {
                flyout.style.left = (ctxRect.width + gap) + 'px';
                flyout.style.right = '';
            } else {
                flyout.style.left = '';
                flyout.style.right = (ctxRect.width + gap) + 'px';
            }
            flyout.style.top = '44px';
        } catch (e) {}
    }

    function showFlyout(which) {
        if (!ctx) return;
        if (subTransform) subTransform.style.display = 'none';
        if (subColor) subColor.style.display = 'none';
        var fly = null;
        if (which === 'transform') fly = subTransform;
        if (which === 'color') fly = subColor;
        if (!fly) return;
        positionFlyout(fly);
        fly.style.display = 'block';
    }

    function showCtxAtRect(rect) {
        if (!rect) return;
        var x = (rect.left || 0);
        var y = (rect.bottom || 0) + 8;
        showCtx(x, y);
    }

    function ensureToolbarButtons(toolbarRoot) {
        if (!toolbarRoot) return;
        try {
            var toolbar = toolbarRoot.closest ? toolbarRoot.closest('.ce-inline-toolbar') : null;
            if (toolbar && toolbar.querySelector && toolbar.querySelector('.tuq-ce-action-btn')) return;
        } catch (e) {}

        if (toolbarRoot.querySelector('.tuq-ce-action-btn[data-tuq="transform"]')) return;

        var btnTransform = document.createElement('button');
        btnTransform.type = 'button';
        btnTransform.className = 'tuq-ce-action-btn';
        btnTransform.setAttribute('data-tuq', 'transform');
        btnTransform.innerHTML = '↺<span>Transformar</span>';

        var btnColor = document.createElement('button');
        btnColor.type = 'button';
        btnColor.className = 'tuq-ce-action-btn';
        btnColor.setAttribute('data-tuq', 'color');
        btnColor.innerHTML = 'A<span>Cor</span>';

        btnTransform.addEventListener('click', function (e) {
            try { if (e) e.preventDefault(); } catch (err) {}
            try { if (e) e.stopPropagation(); } catch (err2) {}
            currentBlockIndex = resolveBlockIndex();
            var r = toolbarRoot.getBoundingClientRect();
            showCtxAtRect(r);
            showFlyout('transform');
        });
        btnColor.addEventListener('click', function (e) {
            try { if (e) e.preventDefault(); } catch (err) {}
            try { if (e) e.stopPropagation(); } catch (err2) {}
            currentBlockIndex = resolveBlockIndex();
            var r = toolbarRoot.getBoundingClientRect();
            showCtxAtRect(r);
            showFlyout('color');
        });

        toolbarRoot.appendChild(btnTransform);
        toolbarRoot.appendChild(btnColor);
    }

    function wireInlineToolbarButtons() {
        try {
            var toolbars = document.querySelectorAll('.ce-inline-toolbar');
            for (var i = 0; i < toolbars.length; i++) {
                var tb = toolbars[i];
                if (!tb || !tb.querySelector) continue;
                var root = tb.querySelector('.ce-inline-toolbar__actions') || tb.querySelector('.ce-inline-toolbar__buttons');
                ensureToolbarButtons(root);
            }
        } catch (e) {}
    }

    function clamp(v, min, max) {
        return Math.min(max, Math.max(min, v));
    }

    function showCtx(x, y) {
        if (!ctx) return;
        var w = 280;
        var h = Math.min(520, (window.innerHeight || 800) - 24);
        var vx = clamp(x, 8, (window.innerWidth || 1200) - w - 8);
        var vy = clamp(y, 8, (window.innerHeight || 800) - h - 8);
        ctx.style.left = vx + 'px';
        ctx.style.top = vy + 'px';
        ctx.style.display = 'block';
        if (subTransform) subTransform.style.display = 'none';
        if (subColor) subColor.style.display = 'none';
        setTimeout(function () { if (ctxSearch) ctxSearch.focus(); }, 10);
    }

    function getBlockIndexFromTarget(target) {
        if (!target) return null;
        var el = target.nodeType === 3 ? target.parentElement : target;
        if (!el || !el.closest) return null;
        var block = el.closest('.ce-block');
        if (!block) return null;
        if (!block.parentElement) return null;
        var blocks = Array.prototype.slice.call(document.querySelectorAll('.ce-block'));
        var idx = blocks.indexOf(block);
        return idx >= 0 ? idx : null;
    }

    function captureSelectionState() {
        try {
            var sel = window.getSelection ? window.getSelection() : null;
            if (!sel || !sel.rangeCount) return;
            var r = sel.getRangeAt(0);
            if (!r) return;

            var container = r.startContainer || null;
            var inside = false;
            try {
                var el = (container && container.nodeType === 3) ? container.parentElement : container;
                inside = !!(el && el.closest && el.closest('#editorjs'));
            } catch (e) { inside = false; }
            if (!inside) return;

            lastSelectionRange = r.cloneRange ? r.cloneRange() : r;
            lastSelectionBlockIndex = getBlockIndexFromTarget(container);
        } catch (e) {}
    }

    try {
        document.addEventListener('selectionchange', function () {
            captureSelectionState();
        });
    } catch (e) {}

    function restoreSelectionRange() {
        try {
            if (!lastSelectionRange) return false;
            var sel = window.getSelection ? window.getSelection() : null;
            if (!sel) return false;
            sel.removeAllRanges();
            sel.addRange(lastSelectionRange);
            return true;
        } catch (e) {
            return false;
        }
    }

    function getSelectionIsExpanded() {
        try {
            if (!lastSelectionRange) return false;
            return !!(lastSelectionRange && !lastSelectionRange.collapsed);
        } catch (e) { return false; }
    }

    function applyInlineTextColor(value) {
        if (!value) return false;
        if (!restoreSelectionRange()) return false;
        var map = {
            gray: '#94a3b8',
            red: '#ef4444',
            yellow: '#eab308',
            green: '#22c55e',
            blue: '#3b82f6'
        };
        var color = map[value] || value;
        try {
            document.execCommand('styleWithCSS', false, true);
        } catch (e) {}
        try {
            document.execCommand('foreColor', false, color);
            return true;
        } catch (e) {
            return false;
        }
    }

    function applyVisualColorToBlock(kind, value) {
        if (currentBlockIndex === null) return;
        var blocks = Array.prototype.slice.call(document.querySelectorAll('.ce-block'));
        var block = blocks[currentBlockIndex];
        if (!block) return;

        var classes = block.className.split(/\s+/).filter(Boolean);
        classes = classes.filter(function (c) {
            return c.indexOf('tuq-block--c-') !== 0 && c.indexOf('tuq-block--bg-') !== 0;
        });
        if (kind === 'text') {
            classes.push('tuq-block--c-' + value);
        } else if (kind === 'bg') {
            classes.push('tuq-block--bg-' + value);
        }
        block.className = classes.join(' ');
    }

    function getBlockText(block) {
        if (!block) return '';
        try {
            var t = (block.type || block.name || '');
            if (t === 'header') {
                return (block.data && block.data.text) ? String(block.data.text) : '';
            }
            if (t === 'paragraph') {
                return (block.data && block.data.text) ? String(block.data.text) : '';
            }
            if (t === 'quote') {
                return (block.data && block.data.text) ? String(block.data.text) : '';
            }
            if (t === 'code') {
                return (block.data && block.data.code) ? String(block.data.code) : '';
            }
        } catch (e) {}
        return '';
    }

    function getBlockTextFromDomByIndex(idx) {
        try {
            if (typeof idx !== 'number' || idx < 0) return '';
            var blocks = Array.prototype.slice.call(document.querySelectorAll('.ce-block'));
            var block = blocks[idx];
            if (!block) return '';
            var content = block.querySelector('.ce-block__content');
            if (!content) content = block;
            var txt = (content.innerText || '').trim();
            return txt;
        } catch (e) {
            return '';
        }
    }

    function transformBlock(to, opts) {
        if (!editor || currentBlockIndex === null) return;
        var idx = currentBlockIndex;
        var cur = null;
        try {
            cur = editor.blocks.getBlockByIndex(idx);
        } catch (e) { cur = null; }
        if (!cur) return;

        var txt = getBlockText(cur);
        if (!txt) {
            var domTxt = getBlockTextFromDomByIndex(idx);
            if (domTxt) txt = domTxt;
        }
        var data = {};
        if (to === 'header') {
            data = { text: txt, level: parseInt((opts && opts.level) || '2', 10) || 2 };
        } else if (to === 'quote') {
            data = { text: txt, caption: '' };
        } else if (to === 'code') {
            data = { code: txt };
        } else if (to === 'list') {
            var items = [];
            if (txt) {
                items = String(txt).split(/\n+/).map(function (s) { return (s || '').trim(); }).filter(Boolean);
                if (!items.length) items = [String(txt)];
            }
            data = { style: (opts && opts.style) ? opts.style : 'unordered', items: items };
        } else if (to === 'checklist') {
            data = { items: txt ? [{ text: txt, checked: false }] : [{ text: '', checked: false }] };
        }

        try {
            editor.blocks.insert(to, data, {}, idx, true);
            try { editor.blocks.delete(idx + 1); } catch (e2) {}
            currentBlockIndex = idx;
        } catch (e) {}
    }

    function resolveBlockIndex() {
        if (currentBlockIndex !== null) return currentBlockIndex;
        if (typeof lastSelectionBlockIndex === 'number' && lastSelectionBlockIndex >= 0) return lastSelectionBlockIndex;
        try {
            if (editor && editor.blocks && typeof editor.blocks.getCurrentBlockIndex === 'function') {
                var i = editor.blocks.getCurrentBlockIndex();
                if (typeof i === 'number' && i >= 0) return i;
            }
        } catch (e) {}
        return null;
    }

    function duplicateBlock() {
        if (!editor) return;
        var idx = resolveBlockIndex();
        if (idx === null) return;

        // Maneira mais confiável: duplicar a partir do state salvo
        editor.save().then(function (data) {
            var blocks = (data && data.blocks) ? data.blocks : [];
            var b = blocks[idx];
            if (!b || !b.type) return;
            try {
                editor.blocks.insert(b.type, b.data || {}, {}, idx + 1, true);
                currentBlockIndex = idx + 1;
                debounceSave();
            } catch (e) {
                setHint('Não foi possível duplicar o bloco.');
                try { console.error('duplicateBlock failed', e); } catch (err) {}
            }
        }).catch(function (e) {
            setHint('Não foi possível duplicar o bloco.');
            try { console.error('duplicateBlock save failed', e); } catch (err) {}
        });
    }

    function deleteBlock() {
        if (!editor) return;
        var idx = resolveBlockIndex();
        if (idx === null) return;
        try { editor.blocks.delete(idx); } catch (e) {}
        currentBlockIndex = null;
        debounceSave();
    }

    if (editor && canEdit) {
        // Traduzir UI padrão do Editor.js (itens do menu de configurações) para PT-BR
        function translateEditorUi(root) {
            var map = {
                'Filter': 'Filtrar',
                'Search': 'Pesquisar',
                'Move up': 'Mover para cima',
                'Move down': 'Mover para baixo',
                'Delete': 'Excluir',
                'Delete block': 'Excluir',
                'Convert to': 'Transformar em',
                'Heading': 'Título',
                'List': 'Lista',
                'Checklist': 'Lista de tarefas',
                'Quote': 'Citação',
                'Code': 'Código',
                'Add': 'Adicionar'
            };

            try {
                var nodes = (root || document).querySelectorAll('button, div, span, input');
                for (var i = 0; i < nodes.length; i++) {
                    var el = nodes[i];
                    if (!el) continue;

                    if (el.tagName && el.tagName.toLowerCase() === 'input') {
                        var ph = el.getAttribute('placeholder') || '';
                        if (map[ph]) el.setAttribute('placeholder', map[ph]);
                        continue;
                    }

                    var txt = (el.textContent || '').trim();
                    if (map[txt]) {
                        el.textContent = map[txt];
                    }
                }
            } catch (e) {}
        }

        translateEditorUi(document);
        wireInlineToolbarButtons();
        try {
            var mo = new MutationObserver(function (mutations) {
                try {
                    for (var i = 0; i < mutations.length; i++) {
                        var m = mutations[i];
                        if (!m) continue;
                        if (m.addedNodes && m.addedNodes.length) {
                            for (var j = 0; j < m.addedNodes.length; j++) {
                                var n = m.addedNodes[j];
                                if (!n || !n.querySelectorAll) continue;
                                translateEditorUi(n);
                            }
                        }
                    }
                    wireInlineToolbarButtons();
                } catch (e) {}
            });
            mo.observe(document.body, { childList: true, subtree: true });
        } catch (e) {}

        document.addEventListener('contextmenu', function (e) {
            var target = e && e.target ? e.target : null;
            if (!target) return;
            var inside = false;
            try {
                inside = !!(target.closest && target.closest('#editorjs'));
            } catch (err) { inside = false; }
            if (!inside) return;
            e.preventDefault();
            currentBlockIndex = getBlockIndexFromTarget(target);
            showCtx(e.clientX, e.clientY);
        });

        function getCaretPositionForMenu() {
            try {
                var sel = window.getSelection ? window.getSelection() : null;
                if (!sel || !sel.rangeCount) return null;
                var range = sel.getRangeAt(0);
                if (!range) return null;
                var rect = range.getBoundingClientRect ? range.getBoundingClientRect() : null;
                if (rect && rect.left && rect.top) {
                    return { x: rect.left, y: rect.bottom + 8 };
                }
            } catch (e) {}
            return null;
        }

        // Slash command: abrir menu estilo Notion
        document.addEventListener('keydown', function (e) {
            if (!e) return;
            if (e.key !== '/') return;
            if (e.ctrlKey || e.metaKey || e.altKey) return;

            var t = e.target || null;
            if (!t) return;
            var tag = (t.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea') return;

            var inside = false;
            try { inside = !!(t.closest && t.closest('#editorjs')); } catch (err) { inside = false; }
            if (!inside) return;

            e.preventDefault();
            currentBlockIndex = getBlockIndexFromTarget(t);
            var pos = getCaretPositionForMenu();
            if (pos) {
                showCtx(pos.x, pos.y);
            } else {
                showCtx((window.innerWidth || 1200) / 2 - 120, (window.innerHeight || 800) / 2 - 120);
            }
            if (ctxSearch) {
                ctxSearch.value = '';
                try {
                    ctxSearch.dispatchEvent(new Event('input'));
                } catch (err2) {}
            }
        }, true);

        // Paste image (clipboard)
        document.addEventListener('paste', function (e) {
            if (!e || !e.clipboardData || !e.clipboardData.items) return;
            var t = e.target || null;
            var inside = false;
            try { inside = !!(t && t.closest && t.closest('#editorjs')); } catch (err) { inside = false; }
            if (!inside) return;

            var atIndex = undefined;
            try {
                currentBlockIndex = getBlockIndexFromTarget(t);
                if (typeof currentBlockIndex === 'number' && currentBlockIndex >= 0) atIndex = currentBlockIndex + 1;
            } catch (err0) {}

            var items = e.clipboardData.items;
            for (var i = 0; i < items.length; i++) {
                var it = items[i];
                if (!it) continue;
                if (it.kind === 'file') {
                    var f = it.getAsFile ? it.getAsFile() : null;
                    if (f && f.type && f.type.indexOf('image/') === 0) {
                        e.preventDefault();
                        setHint('Enviando imagem...');
                        uploadMediaFile(f).then(function (res) {
                            if (res.json && res.json.success === 1 && res.json.file && res.json.file.url) {
                                insertImageByUrl(res.json.file.url, atIndex);
                                setHint('Imagem enviada');
                            } else {
                                showActionError({ json: { error: (res.json && res.json.message) ? res.json.message : 'Falha ao enviar imagem' }, status: res.status }, 'Falha ao enviar imagem');
                            }
                        });
                        return;
                    }
                }
            }
        }, true);

        // Drop image/file
        document.addEventListener('drop', function (e) {
            if (!e || !e.dataTransfer || !e.dataTransfer.files || !e.dataTransfer.files.length) return;
            var t = e.target || null;
            var inside = false;
            try { inside = !!(t && t.closest && t.closest('#editorjs')); } catch (err) { inside = false; }
            if (!inside) return;

            e.preventDefault();
            var atIndex = undefined;
            try {
                currentBlockIndex = getBlockIndexFromTarget(t);
                if (typeof currentBlockIndex === 'number' && currentBlockIndex >= 0) atIndex = currentBlockIndex + 1;
            } catch (err0) {}
            var f = e.dataTransfer.files[0];
            if (!f) return;
            setHint('Enviando arquivo...');
            uploadMediaFile(f).then(function (res) {
                if (res.json && res.json.success === 1 && res.json.file) {
                    if (f.type && f.type.indexOf('image/') === 0 && res.json.file.url) {
                        insertImageByUrl(res.json.file.url, atIndex);
                        setHint('Imagem enviada');
                    } else {
                        if (!res.json.file.url) {
                            showActionError({ json: { error: (res.json && res.json.message) ? res.json.message : 'Upload retornou sem URL' }, status: res.status }, 'Falha ao enviar arquivo');
                            return;
                        }
                        insertFileByMeta(res.json.file, atIndex);
                        setHint('Arquivo enviado');
                    }
                } else {
                    showActionError({ json: { error: (res.json && res.json.message) ? res.json.message : 'Falha ao enviar arquivo' }, status: res.status }, 'Falha ao enviar arquivo');
                }
            });
        }, true);

        document.addEventListener('dragover', function (e) {
            var t = e && e.target ? e.target : null;
            var inside = false;
            try { inside = !!(t && t.closest && t.closest('#editorjs')); } catch (err) { inside = false; }
            if (!inside) return;
            e.preventDefault();
        }, true);

        document.addEventListener('click', function (e) {
            if (!ctx || ctx.style.display !== 'block') return;
            var t = e && e.target ? e.target : null;
            if (t && (t === ctx || (t.closest && t.closest('#tuq-ctx')))) return;
            hideCtx();
        });

        document.addEventListener('keydown', function (e) {
            if (!e) return;
            if (e.key === 'Escape') hideCtx();
            if (e.key === 'Delete' && ctx && ctx.style.display === 'block') {
                e.preventDefault();
                deleteBlock();
                hideCtx();
            }
            if ((e.ctrlKey || e.metaKey) && (e.key || '').toLowerCase() === 'd' && ctx && ctx.style.display === 'block') {
                e.preventDefault();
                duplicateBlock();
                hideCtx();
            }
        });

        if (ctx) {
            ctx.addEventListener('click', function (e) {
                var t = e && e.target ? e.target : null;
                if (!t) return;
                if (t && t.nodeType === 3) t = t.parentElement;
                var item = t && t.closest ? t.closest('.tuq-ctx-item') : null;
                if (!item) return;

                var action = item.getAttribute('data-action');
                if (action === 'toggle-transform') {
                    showFlyout('transform');
                    return;
                }
                if (action === 'toggle-color') {
                    showFlyout('color');
                    return;
                }
                if (action === 'create-subpage') {
                    if (!pageId) return;
                    var atIndex = undefined;
                    try {
                        var idx = resolveBlockIndex();
                        if (typeof idx === 'number' && idx >= 0) atIndex = idx + 1;
                    } catch (e0) {}
                    hideCtx();

                    postForm('/caderno/criar', { title: 'Sem título', parent_id: String(pageId) }).then(function (res) {
                        if (!res.json || !res.json.ok || !res.json.id) {
                            showActionError(res, 'Falha ao criar subpágina.');
                            return;
                        }

                        var newId = String(res.json.id);
                        var href = '/caderno?id=' + encodeURIComponent(newId);
                        if (!editor || !canEdit) {
                            window.location.href = href;
                            return;
                        }

                        try {
                            editor.blocks.insert('subpage', { id: newId, title: 'Sem título', icon: '📄' }, {}, atIndex, true);
                        } catch (eIns) {
                            window.location.href = href;
                            return;
                        }

                        editor.save().then(function (data) {
                            return postForm('/caderno/salvar', {
                                page_id: String(pageId),
                                content_json: JSON.stringify(data)
                            });
                        }).then(function (saveRes) {
                            if (!saveRes || !saveRes.json || saveRes.json.ok !== true) {
                                showActionError(saveRes, 'Falha ao salvar a página.');
                                return;
                            }
                            window.location.href = href;
                        });
                    });
                    return;
                }
                if (action === 'insert-image') {
                    var atIndex = undefined;
                    try {
                        var idx = resolveBlockIndex();
                        if (typeof idx === 'number' && idx >= 0) atIndex = idx + 1;
                    } catch (e0) {}
                    hideCtx();
                    pickFile('image/*').then(function (f) {
                        if (!f) return;
                        setHint('Enviando imagem...');
                        uploadMediaFile(f).then(function (res) {
                            if (res.json && res.json.success === 1 && res.json.file && res.json.file.url) {
                                insertImageByUrl(res.json.file.url, atIndex);
                                setHint('Imagem enviada');
                            } else {
                                showActionError({ json: { error: (res.json && res.json.message) ? res.json.message : 'Falha ao enviar imagem' }, status: res.status }, 'Falha ao enviar imagem');
                            }
                        });
                    });
                    return;
                }
                if (action === 'insert-file') {
                    var atIndex = undefined;
                    try {
                        var idx = resolveBlockIndex();
                        if (typeof idx === 'number' && idx >= 0) atIndex = idx + 1;
                    } catch (e0) {}
                    hideCtx();
                    pickFile('*/*').then(function (f) {
                        if (!f) return;
                        setHint('Enviando arquivo...');
                        uploadMediaFile(f).then(function (res) {
                            if (res.json && res.json.success === 1 && res.json.file) {
                                if (!res.json.file.url) {
                                    showActionError({ json: { error: (res.json && res.json.message) ? res.json.message : 'Upload retornou sem URL' }, status: res.status }, 'Falha ao enviar arquivo');
                                    return;
                                }
                                insertFileByMeta(res.json.file, atIndex);
                                setHint('Arquivo enviado');
                            } else {
                                showActionError({ json: { error: (res.json && res.json.message) ? res.json.message : 'Falha ao enviar arquivo' }, status: res.status }, 'Falha ao enviar arquivo');
                            }
                        });
                    });
                    return;
                }
                if (action === 'duplicate') {
                    duplicateBlock();
                    hideCtx();
                    return;
                }
                if (action === 'delete') {
                    deleteBlock();
                    hideCtx();
                    return;
                }
                if (action === 'transform') {
                    transformBlock(item.getAttribute('data-to'), {
                        level: item.getAttribute('data-level'),
                        style: item.getAttribute('data-style')
                    });
                    hideCtx();
                    debounceSave();
                    return;
                }
                if (action === 'color') {
                    var kind = item.getAttribute('data-kind');
                    var value = item.getAttribute('data-value');
                    if (kind === 'text' && getSelectionIsExpanded()) {
                        var ok = applyInlineTextColor(value);
                        if (ok) {
                            hideCtx();
                            debounceSave();
                            return;
                        }
                    }
                    applyVisualColorToBlock(kind, value);
                    hideCtx();
                    debounceSave();
                    return;
                }
            });
        }

        if (ctxSearch) {
            ctxSearch.addEventListener('input', function () {
                var q = (ctxSearch.value || '').toLowerCase().trim();
                var items = Array.prototype.slice.call(ctx.querySelectorAll('.tuq-ctx-item'));
                items.forEach(function (it) {
                    var text = (it.textContent || '').toLowerCase();
                    if (q === '' || text.indexOf(q) >= 0) {
                        it.style.display = '';
                    } else {
                        it.style.display = 'none';
                    }
                });
            });
        }
    }

    var btnNew = $('btn-new-page');
    var newMenu = $('new-page-menu');
    if (btnNew) {
        btnNew.addEventListener('click', function () {
            if (newMenu) {
                newMenu.style.display = (newMenu.style.display === 'block') ? 'none' : 'block';
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!newMenu || newMenu.style.display !== 'block') return;
        var t = e && e.target ? e.target : null;
        if (t && t.closest && (t.closest('#new-page-menu') || t.closest('#btn-new-page'))) return;
        newMenu.style.display = 'none';
    });

    if (newMenu) {
        newMenu.addEventListener('click', function (e) {
            var t = e && e.target ? e.target : null;
            if (t && t.nodeType === 3) t = t.parentElement;
            var btn = t && t.closest ? t.closest('.new-page-menu-item') : null;
            if (!btn) return;
            if (btn.disabled) return;

            var kind = String(btn.getAttribute('data-new-kind') || 'page');
            var payload = { title: 'Sem título' };
            if (kind === 'subpage') {
                if (!pageId) return;
                payload.parent_id = String(pageId);
            }
            newMenu.style.display = 'none';
            postForm('/caderno/criar', payload).then(function (res) {
                if (res.json && res.json.ok && res.json.id) {
                    window.location.href = '/caderno?id=' + encodeURIComponent(res.json.id);
                } else {
                    showActionError(res, 'Falha ao criar página.');
                }
            });
        });
    }

    function attachRename() {
        var title = $('page-title');
        var icon = $('page-icon');
        if (!title || !icon) return;
        if (!pageId) return;
        if (!canEdit) return;

        var timer = null;
        function doRename() {
            postForm('/caderno/renomear', {
                page_id: String(pageId),
                title: title.value || 'Sem título',
                icon: icon.value || ''
            }).then(function (res) {
                if (res && res.json && res.json.ok) return;
                if (res && res.text && res.text !== '') {
                    showActionError(res, 'Falha ao renomear.');
                    return;
                }
                if (res && res.json && res.json.error) {
                    showActionError(res, 'Falha ao renomear.');
                }
            });
        }
        function schedule() {
            if (timer) clearTimeout(timer);
            timer = setTimeout(doRename, 500);
        }
        title.addEventListener('input', schedule);
        icon.addEventListener('input', schedule);
    }
    attachRename();

    function setupEmojiPicker() {
        var iconWrap = document.querySelector('.notion-emoji');
        var iconInput = $('page-icon');
        var backdrop = $('emoji-picker-backdrop');
        var grid = $('emoji-picker-grid');
        var search = $('emoji-picker-search');
        var close = $('emoji-picker-close');
        if (!iconWrap || !iconInput || !backdrop || !grid || !search || !close) return;

        function open() {
            if (!canEdit) return;
            if (!pageId) return;
            backdrop.style.display = 'flex';
            backdrop.setAttribute('aria-hidden', 'false');
            search.value = '';
            render('');
            setTimeout(function () {
                try { search.focus(); } catch (e) {}
            }, 0);
        }

        function hide() {
            backdrop.style.display = 'none';
            backdrop.setAttribute('aria-hidden', 'true');
        }

        var EMOJIS = [
            { e: '📄', k: 'pagina documento arquivo folha' },
            { e: '📁', k: 'pasta arquivos' },
            { e: '📌', k: 'pin fixar' },
            { e: '✅', k: 'check ok concluido' },
            { e: '🧠', k: 'ideia mente' },
            { e: '💡', k: 'ideia lampada' },
            { e: '🔥', k: 'fogo hot' },
            { e: '⭐', k: 'estrela favorito' },
            { e: '⚡', k: 'raio rapido' },
            { e: '📝', k: 'anotacao escrever' },
            { e: '📚', k: 'livros estudo' },
            { e: '🎯', k: 'alvo objetivo meta' },
            { e: '🚀', k: 'foguete lancar' },
            { e: '🧩', k: 'puzzle' },
            { e: '🗂️', k: 'organizar kanban arquivos' },
            { e: '🧾', k: 'recibo lista' },
            { e: '📅', k: 'calendario agenda' },
            { e: '⏰', k: 'tempo relogio' },
            { e: '🎨', k: 'design arte' },
            { e: '🧑‍💻', k: 'codigo dev' },
            { e: '📈', k: 'crescimento grafico' },
            { e: '🛒', k: 'vendas loja' },
            { e: '💰', k: 'dinheiro' },
            { e: '📣', k: 'marketing megafone' },
            { e: '📷', k: 'foto imagem' },
            { e: '🎬', k: 'video' },
            { e: '🔒', k: 'seguranca' },
            { e: '🔓', k: 'aberto' },
            { e: '🧪', k: 'teste' },
            { e: '⚠️', k: 'alerta' },
            { e: '💎', k: 'premium' },
            { e: '🏁', k: 'final' },
            { e: '🌱', k: 'inicio free' },
        ];

        function setEmoji(emoji) {
            iconInput.value = emoji;
            try {
                iconInput.dispatchEvent(new Event('input', { bubbles: true }));
            } catch (e) {
            }
            hide();
        }

        function render(query) {
            var q = String(query || '').toLowerCase().trim();
            grid.innerHTML = '';
            var list = EMOJIS.filter(function (it) {
                if (!q) return true;
                var key = (it.k || '') + ' ' + (it.e || '');
                return key.toLowerCase().indexOf(q) >= 0;
            });
            if (!list.length) {
                var empty = document.createElement('div');
                empty.style.gridColumn = '1 / -1';
                empty.style.fontSize = '12px';
                empty.style.color = 'var(--text-secondary)';
                empty.textContent = 'Nenhum emoji encontrado.';
                grid.appendChild(empty);
                return;
            }
            list.forEach(function (it) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'tuq-emoji-btn';
                b.textContent = it.e;
                b.addEventListener('click', function () { setEmoji(it.e); });
                grid.appendChild(b);
            });
        }

        iconWrap.addEventListener('click', function (e) {
            if (e) e.preventDefault();
            open();
        });
        iconInput.addEventListener('focus', function () {
            open();
        });
        close.addEventListener('click', hide);
        backdrop.addEventListener('click', function (e) {
            if (e && e.target === backdrop) hide();
        });
        window.addEventListener('keydown', function (e) {
            if (!e) return;
            if (backdrop.style.display !== 'flex') return;
            if (e.key === 'Escape') hide();
        });
        search.addEventListener('input', function () {
            render(search.value || '');
        });
    }
    setupEmojiPicker();

    function setupAutoResizeTextareas() {
        var host = document.getElementById('editorjs');
        if (!host) return;

        function autoSize(el) {
            if (!el || el.nodeType !== 1) return;
            var tag = (el.tagName || '').toLowerCase();
            var isTextarea = tag === 'textarea';
            var isEditableQuote = !isTextarea && (el.classList && el.classList.contains('cdx-quote__text'));
            if (!isTextarea && !isEditableQuote) return;

            try {
                el.style.height = 'auto';
                el.style.overflow = 'hidden';
                var h = el.scrollHeight;
                if (typeof h === 'number' && h > 0) {
                    el.style.height = h + 'px';
                }
            } catch (e) {}
        }

        function scan() {
            var areas = host.querySelectorAll('textarea, .cdx-quote__text');
            for (var i = 0; i < areas.length; i++) {
                autoSize(areas[i]);
            }
        }

        host.addEventListener('input', function (e) {
            var t = e && e.target ? e.target : null;
            autoSize(t);
        });

        // captura blocos inseridos dinamicamente
        try {
            var obs = new MutationObserver(function () {
                scan();
            });
            obs.observe(host, { childList: true, subtree: true });
        } catch (e) {}

        // inicial
        setTimeout(scan, 0);
        setTimeout(scan, 250);
    }
    setupAutoResizeTextareas();

    function setupPlusOpensTransformMenu() {
        if (!canEdit) return;

        function handlePlusClick(btn, e) {
            try { if (e) e.preventDefault(); } catch (err) {}
            try { if (e) e.stopPropagation(); } catch (err2) {}

            try {
                currentBlockIndex = resolveBlockIndex();
            } catch (err3) {
                currentBlockIndex = null;
            }

            try {
                var r = btn.getBoundingClientRect();
                showCtxAtRect(r);
                showFlyout('transform');
            } catch (err4) {}

            if (ctxSearch) {
                ctxSearch.value = '';
                try {
                    ctxSearch.dispatchEvent(new Event('input'));
                } catch (err5) {}
            }
        }

        // Captura antes do Editor.js (para evitar abrir o popover padrão do '+')
        document.addEventListener('click', function (e) {
            var t = e && e.target ? e.target : null;
            if (!t) return;
            var btn = null;
            try {
                btn = t.closest ? t.closest('.ce-toolbar__plus') : null;
            } catch (err) {
                btn = null;
            }
            if (!btn) return;

            // Apenas quando o '+' estiver dentro do editor
            var inside = false;
            try { inside = !!(btn.closest && btn.closest('#editorjs')); } catch (err2) { inside = false; }
            if (!inside) return;

            handlePlusClick(btn, e);
        }, true);
    }
    setupPlusOpensTransformMenu();

    var btnDelete = $('btn-delete');
    if (btnDelete && pageId) {
        btnDelete.addEventListener('click', function () {
            if (!confirm('Excluir esta página?')) return;
            postForm('/caderno/excluir', { page_id: String(pageId) }).then(function (res) {
                if (res.json && res.json.ok) {
                    window.location.href = '/caderno';
                } else {
                    showActionError(res, 'Falha ao excluir.');
                }
            });
        });
    }

    var btnShare = $('btn-share');
    if (btnShare) {
        btnShare.addEventListener('click', function () {
            var p = $('share-panel');
            if (!p) return;
            p.style.display = (p.style.display === 'none' || p.style.display === '') ? 'block' : 'none';
        });
    }

    function renderShares(shares) {
        var list = $('share-list');
        if (!list) return;
        list.innerHTML = '';
        if (!shares || !shares.length) {
            var div = document.createElement('div');
            div.style.fontSize = '12px';
            div.style.color = 'var(--text-secondary)';
            div.textContent = 'Ninguém ainda.';
            list.appendChild(div);
            return;
        }
        shares.forEach(function (s) {
            var row = document.createElement('div');
            row.style.display = 'flex';
            row.style.alignItems = 'center';
            row.style.justifyContent = 'space-between';
            row.style.gap = '10px';
            row.style.border = '1px solid var(--border-subtle)';
            row.style.background = 'var(--surface-subtle)';
            row.style.padding = '8px 10px';
            row.style.borderRadius = '10px';

            var left = document.createElement('div');
            left.style.minWidth = '0';

            var email = document.createElement('div');
            email.style.fontSize = '13px';
            email.style.color = 'var(--text-primary)';
            email.style.overflow = 'hidden';
            email.style.textOverflow = 'ellipsis';
            email.style.whiteSpace = 'nowrap';
            email.textContent = s.email || '';

            var role = document.createElement('div');
            role.style.fontSize = '11px';
            role.style.color = 'var(--text-secondary)';
            role.textContent = s.role || 'view';

            left.appendChild(email);
            left.appendChild(role);

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = 'Remover';
            btn.style.border = '1px solid var(--border-subtle)';
            btn.style.borderRadius = '999px';
            btn.style.padding = '6px 10px';
            btn.style.background = 'transparent';
            btn.style.color = 'var(--text-primary)';
            btn.style.fontSize = '12px';
            btn.style.cursor = 'pointer';
            btn.addEventListener('click', function () {
                postForm('/caderno/compartilhar/remover', { page_id: String(pageId), user_id: String(s.user_id || 0) }).then(function (res) {
                    if (res.json && res.json.ok) {
                        renderShares(res.json.shares || []);
                    }
                });
            });

            row.appendChild(left);
            row.appendChild(btn);
            list.appendChild(row);
        });
    }

    var btnShareAdd = $('btn-share-add');
    if (btnShareAdd && pageId) {
        btnShareAdd.addEventListener('click', function () {
            var email = $('share-email');
            var role = $('share-role');
            if (!email || !role) return;
            postForm('/caderno/compartilhar/adicionar', {
                page_id: String(pageId),
                email: email.value || '',
                role: role.value || 'view'
            }).then(function (res) {
                if (res.json && res.json.ok) {
                    email.value = '';
                    renderShares(res.json.shares || []);
                } else {
                    showActionError(res, 'Falha ao compartilhar.');
                }
            });
        });
    }

    var btnPublish = $('btn-publish');
    if (btnPublish && pageId) {
        btnPublish.addEventListener('click', function () {
            var willPublish = <?= $isPublished ? 'false' : 'true' ?>;
            postForm('/caderno/publicar', { page_id: String(pageId), publish: willPublish ? '1' : '' }).then(function (res) {
                if (res.json && res.json.ok) {
                    window.location.reload();
                } else {
                    showActionError(res, 'Falha ao publicar.');
                }
            });
        });
    }
})();
</script>
