<?php
/** @var array $user */
/** @var array $boards */
/** @var array|null $currentBoard */
/** @var array $lists */
/** @var array $cardsByList */

$currentBoardId = $currentBoard ? (int)($currentBoard['id'] ?? 0) : 0;
$currentBoardTitle = $currentBoard ? (string)($currentBoard['title'] ?? 'Sem título') : 'Kanban';
?>

<style>
    .kb-shell * {
        box-sizing: border-box;
    }

    .kb-shell {
        display: flex;
        gap: 12px;
        min-height: calc(100vh - 64px);
    }

    body.kb-sidebar-collapsed .kb-shell {
        gap: 0;
    }
    .kb-sidebar {
        width: 300px;
        flex: 0 0 300px;
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        background: var(--surface-card);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: width 160ms ease, flex-basis 160ms ease, opacity 160ms ease;
    }

    body.kb-sidebar-collapsed .kb-sidebar {
        width: 0;
        flex: 0 0 0;
        opacity: 0;
        border: none;
        margin: 0;
        padding: 0;
    }
    .kb-sidebar-head {
        padding: 12px;
        border-bottom: 1px solid var(--border-subtle);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .kb-sidebar-title {
        font-weight: 750;
        font-size: 13px;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: var(--text-secondary);
    }
    .kb-btn {
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        color: var(--text-primary);
        border-radius: 10px;
        padding: 8px 10px;
        font-size: 12px;
        cursor: pointer;
        line-height: 1;
    }
    .kb-btn:focus {
        outline: 2px solid rgba(229,57,53,0.35);
        outline-offset: 2px;
    }
    .kb-btn--primary {
        border: none;
        background: linear-gradient(135deg,#e53935,#ff6f60);
        color: #050509;
        font-weight: 700;
    }
    .kb-btn--danger {
        border: 1px solid rgba(229,57,53,0.35);
        background: rgba(229,57,53,0.10);
        color: var(--accent);
    }
    .kb-sidebar-list {
        padding: 10px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        overflow-y: auto;
    }

    .kb-share-card {
        width: min(520px, calc(100vw - 28px));
    }
    .kb-share-row {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-top: 10px;
        flex-wrap: wrap;
    }
    .kb-share-list {
        margin-top: 10px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 260px;
        overflow: auto;
        padding-right: 2px;
    }
    .kb-share-empty {
        color: var(--text-secondary);
        font-size: 12px;
        padding: 6px 2px;
    }
    .kb-board-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 10px;
        border: 1px solid transparent;
        background: transparent;
        color: var(--text-primary);
        font-size: 13px;
        text-decoration: none;
    }
    .kb-board-item:hover {
        background: rgba(255,255,255,0.04);
        border-color: rgba(255,255,255,0.07);
    }
    body[data-theme="light"] .kb-board-item:hover {
        background: rgba(15,23,42,0.04);
        border-color: rgba(15,23,42,0.08);
    }
    .kb-board-item.is-active {
        background: rgba(229,57,53,0.14);
        border-color: rgba(229,57,53,0.25);
    }
    .kb-board-item-title {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
    }

    .kb-main {
        flex: 1;
        min-width: 0;
        border: 1px solid var(--border-subtle);
        border-radius: 12px;
        background: var(--surface-card);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .kb-main-head {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-subtle);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }
    .kb-main-head-left {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }
    .kb-toggle-sidebar {
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
    .kb-main-title {
        font-size: 18px;
        font-weight: 800;
        color: var(--text-primary);
    }

    .kb-board {
        flex: 1;
        overflow-x: auto;
        overflow-y: hidden;
        padding: 14px 14px 18px 14px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    /* Scrollbars (Kanban) */
    .kb-board,
    .kb-sidebar-list,
    .kb-cards {
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,0.28) rgba(0,0,0,0.45);
    }
    body[data-theme="light"] .kb-board,
    body[data-theme="light"] .kb-sidebar-list,
    body[data-theme="light"] .kb-cards {
        scrollbar-color: rgba(15,23,42,0.35) rgba(15,23,42,0.10);
    }

    .kb-board::-webkit-scrollbar,
    .kb-sidebar-list::-webkit-scrollbar,
    .kb-cards::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    .kb-board::-webkit-scrollbar-track,
    .kb-sidebar-list::-webkit-scrollbar-track,
    .kb-cards::-webkit-scrollbar-track {
        background: rgba(0,0,0,0.55);
        border-radius: 999px;
    }
    .kb-board::-webkit-scrollbar-thumb,
    .kb-sidebar-list::-webkit-scrollbar-thumb,
    .kb-cards::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.95);
        border-radius: 999px;
        border: 2px solid rgba(0,0,0,0.55);
    }
    .kb-board::-webkit-scrollbar-thumb:hover,
    .kb-sidebar-list::-webkit-scrollbar-thumb:hover,
    .kb-cards::-webkit-scrollbar-thumb:hover {
        background: rgba(20,20,20,0.98);
    }

    body[data-theme="light"] .kb-board::-webkit-scrollbar-track,
    body[data-theme="light"] .kb-sidebar-list::-webkit-scrollbar-track,
    body[data-theme="light"] .kb-cards::-webkit-scrollbar-track {
        background: rgba(15,23,42,0.10);
    }
    body[data-theme="light"] .kb-board::-webkit-scrollbar-thumb,
    body[data-theme="light"] .kb-sidebar-list::-webkit-scrollbar-thumb,
    body[data-theme="light"] .kb-cards::-webkit-scrollbar-thumb {
        background: rgba(15,23,42,0.28);
        border: 2px solid rgba(15,23,42,0.10);
    }
    body[data-theme="light"] .kb-board::-webkit-scrollbar-thumb:hover,
    body[data-theme="light"] .kb-sidebar-list::-webkit-scrollbar-thumb:hover,
    body[data-theme="light"] .kb-cards::-webkit-scrollbar-thumb:hover {
        background: rgba(15,23,42,0.38);
    }

    .kb-list {
        width: 290px;
        flex: 0 0 290px;
        border: 1px solid var(--border-subtle);
        background: rgba(255,255,255,0.04);
        border-radius: 12px;
        padding: 10px;
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 64px - 88px - 24px);
    }
    body[data-theme="light"] .kb-list {
        background: rgba(15,23,42,0.04);
    }
    .kb-list.drag-over {
        outline: 2px solid rgba(229,57,53,0.45);
        outline-offset: 2px;
    }
    .kb-list.kb-list--dragging {
        opacity: 0.55;
        transform: rotate(1.2deg);
        cursor: grabbing;
    }
    .kb-list-placeholder {
        width: 290px;
        flex: 0 0 290px;
        border-radius: 12px;
        border: 2px dashed rgba(229,57,53,0.35);
        background: rgba(229,57,53,0.07);
        min-height: 80px;
    }
    body[data-theme="light"] .kb-list-placeholder {
        border: 2px dashed rgba(229,57,53,0.32);
        background: rgba(229,57,53,0.05);
    }
    .kb-list-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 8px;
    }
    .kb-list-title {
        font-size: 13px;
        font-weight: 750;
        color: var(--text-primary);
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
        cursor: text;
    }
    .kb-list-actions {
        display: flex;
        gap: 6px;
        flex: 0 0 auto;
    }

    .kb-cards {
        display: flex;
        flex-direction: column;
        gap: 8px;
        overflow-y: auto;
        padding-right: 3px;
        padding-bottom: 6px;
        min-height: 28px;
        flex: 1;
    }

    .kb-card {
        border: 1px solid var(--border-subtle);
        background: rgba(17,17,24,0.78);
        border-radius: 12px;
        padding: 10px;
        cursor: grab;
        user-select: none;
        color: var(--text-primary);
        box-shadow: 0 10px 22px rgba(0,0,0,0.22);
    }
    .kb-card.kb-card--dragging {
        opacity: 0.55;
        transform: rotate(1.2deg);
        cursor: grabbing;
    }
    .kb-card-placeholder {
        border-radius: 12px;
        border: 2px dashed rgba(229,57,53,0.35);
        background: rgba(229,57,53,0.07);
        height: 52px;
    }
    body[data-theme="light"] .kb-card-placeholder {
        border: 2px dashed rgba(229,57,53,0.32);
        background: rgba(229,57,53,0.05);
    }
    body[data-theme="light"] .kb-card {
        background: #ffffff;
        box-shadow: 0 10px 22px rgba(15,23,42,0.10);
    }
    .kb-card:active {
        cursor: grabbing;
    }
    .kb-card-title {
        font-size: 13px;
        font-weight: 650;
        color: var(--text-primary);
        line-height: 1.35;
        word-break: break-word;
    }
    .kb-card-desc {
        margin-top: 6px;
        font-size: 12px;
        color: var(--text-secondary);
        line-height: 1.35;
        max-height: 44px;
        overflow: hidden;
    }

    .kb-add {
        margin-top: 8px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .kb-input {
        width: 100%;
        padding: 8px 10px;
        border-radius: 10px;
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        color: var(--text-primary);
        font-size: 13px;
        outline: none;
    }

    .kb-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 100000;
    }
    .kb-modal.is-open { display: flex; }
    .kb-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.65);
    }
    body[data-theme="light"] .kb-modal-backdrop {
        background: rgba(15,23,42,0.35);
    }
    .kb-modal-card {
        position: relative;
        width: min(980px, calc(100vw - 28px));
        border-radius: 16px;
        border: 1px solid var(--border-subtle);
        background: var(--surface-card);
        padding: 16px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.35);
        max-height: calc(100vh - 24px);
        overflow-y: auto;
    }

    .kb-modal-two-col {
        display: flex;
        gap: 14px;
        margin-top: 10px;
        align-items: flex-start;
    }
    .kb-modal-left {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .kb-modal-right {
        width: 240px;
        flex: 0 0 240px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .kb-modal-side-title {
        font-size: 12px;
        font-weight: 800;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-top: 2px;
    }
    .kb-qa-btn {
        width: 100%;
        justify-content: flex-start;
        gap: 8px;
    }
    .kb-modal-actions.kb-modal-actions--footer {
        margin-top: 14px;
    }

    @media (max-width: 860px) {
        .kb-modal-card {
            width: min(620px, calc(100vw - 28px));
        }
        .kb-modal-two-col {
            flex-direction: column;
        }
        .kb-modal-right {
            width: 100%;
            flex: 0 0 auto;
        }
    }

    .kb-modal-card,
    .kb-attachments-list {
        scrollbar-color: rgba(255,255,255,0.24) rgba(0,0,0,0.25);
        scrollbar-width: thin;
    }
    .kb-modal-card::-webkit-scrollbar,
    .kb-attachments-list::-webkit-scrollbar {
        width: 10px;
    }
    .kb-modal-card::-webkit-scrollbar-track,
    .kb-attachments-list::-webkit-scrollbar-track {
        background: rgba(0,0,0,0.25);
        border-radius: 999px;
    }
    .kb-modal-card::-webkit-scrollbar-thumb,
    .kb-attachments-list::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.24);
        border-radius: 999px;
        border: 2px solid rgba(0,0,0,0.25);
    }
    .kb-modal-card::-webkit-scrollbar-thumb:hover,
    .kb-attachments-list::-webkit-scrollbar-thumb:hover {
        background: rgba(255,255,255,0.34);
    }

    .kb-cover-box {
        margin-top: 10px;
        border-top: 1px solid var(--border-subtle);
        padding-top: 10px;
    }
    .kb-cover-preview {
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        border-radius: 12px;
        overflow: hidden;
    }
    .kb-cover-preview img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        display: block;
    }
    .kb-cover-empty {
        padding: 10px;
        color: var(--text-secondary);
        font-size: 12px;
    }
    .kb-cover-grid {
        margin-top: 10px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }
    @media (max-width: 720px) {
        .kb-cover-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .kb-cover-thumb {
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        border-radius: 12px;
        overflow: hidden;
        padding: 0;
        cursor: pointer;
        text-align: left;
    }
    .kb-cover-thumb img {
        width: 100%;
        height: 76px;
        object-fit: cover;
        display: block;
    }
    .kb-cover-thumb-meta {
        padding: 8px;
        font-size: 12px;
        color: var(--text-secondary);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
    }

    .kb-attachments {
        margin-top: 10px;
        border-top: 1px solid var(--border-subtle);
        padding-top: 10px;
    }
    .kb-attachments-title {
        font-size: 12px;
        font-weight: 800;
        color: var(--text-secondary);
        letter-spacing: 0.02em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }
    .kb-attachments-row {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    .kb-attachments-list {
        margin-top: 10px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 180px;
        overflow: auto;
        padding-right: 2px;
    }
    .kb-attachment-item {
        display: flex;
        gap: 10px;
        align-items: center;
        justify-content: space-between;
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        border-radius: 12px;
        padding: 10px;
    }
    .kb-attachment-left {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .kb-attachment-name {
        font-size: 13px;
        color: var(--text-primary);
        font-weight: 650;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 360px;
    }
    .kb-attachment-actions {
        flex: 0 0 auto;
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .kb-preview-modal {
        position: fixed;
        inset: 0;
        z-index: 100000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .kb-preview-modal.is-open {
        display: flex;
    }
    .kb-preview-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.70);
    }
    .kb-preview-card {
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
    .kb-preview-head {
        padding: 10px 12px;
        border-bottom: 1px solid var(--border-subtle);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .kb-preview-title {
        font-size: 13px;
        font-weight: 800;
        color: var(--text-primary);
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .kb-preview-body {
        padding: 0;
        overflow: auto;
        background: rgba(0,0,0,0.25);
        flex: 1;
        min-height: 260px;
    }
    body[data-theme="light"] .kb-preview-body {
        background: rgba(15,23,42,0.06);
    }
    /* Scrollbars (preview modal) */
    .kb-preview-body {
        scrollbar-width: thin;
        scrollbar-color: rgba(0,0,0,0.95) rgba(0,0,0,0.55);
    }
    body[data-theme="light"] .kb-preview-body {
        scrollbar-color: rgba(15,23,42,0.35) rgba(15,23,42,0.10);
    }
    .kb-preview-body::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    .kb-preview-body::-webkit-scrollbar-track {
        background: rgba(0,0,0,0.55);
        border-radius: 999px;
    }
    .kb-preview-body::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.95);
        border-radius: 999px;
        border: 2px solid rgba(0,0,0,0.55);
    }
    .kb-preview-body::-webkit-scrollbar-thumb:hover {
        background: rgba(20,20,20,0.98);
    }
    body[data-theme="light"] .kb-preview-body::-webkit-scrollbar-track {
        background: rgba(15,23,42,0.10);
    }
    body[data-theme="light"] .kb-preview-body::-webkit-scrollbar-thumb {
        background: rgba(15,23,42,0.28);
        border: 2px solid rgba(15,23,42,0.10);
    }
    body[data-theme="light"] .kb-preview-body::-webkit-scrollbar-thumb:hover {
        background: rgba(15,23,42,0.38);
    }
    .kb-preview-body img {
        display: block;
        max-width: 100%;
        height: auto;
        margin: 0 auto;
    }
    .kb-preview-frame {
        width: 100%;
        height: 70vh;
        border: none;
        display: block;
        background: transparent;
    }
    @media (max-width: 720px) {
        .kb-attachment-name { max-width: 220px; }
    }
    .kb-modal-title {
        font-size: 14px;
        font-weight: 800;
        color: var(--text-primary);
    }
    .kb-modal-field-label {
        font-size: 12px;
        color: var(--text-secondary);
        font-weight: 750;
        margin-top: 10px;
        margin-bottom: 6px;
    }
    .kb-select {
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid var(--border-subtle);
        background: var(--surface-subtle);
        color: var(--text-primary);
        font-size: 13px;
        outline: none;
    }
    .kb-modal-actions {
        margin-top: 10px;
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    @media (max-width: 720px) {
        .kb-shell { display: block; }
        .kb-sidebar { width: 100%; flex: 0 0 auto; }
        body.kb-sidebar-collapsed .kb-sidebar {
            width: 0;
            flex: 0 0 0;
        }
        .kb-sidebar-head {
            position: sticky;
            top: 0;
            background: var(--surface-card);
            z-index: 2;
        }
        .kb-sidebar-list {
            max-height: 220px;
        }
        .kb-main { margin-top: 12px; }
        .kb-main-head {
            padding: 12px;
        }
        .kb-main-title {
            font-size: 16px;
        }
        .kb-board { padding: 12px; }
        .kb-list { width: 92vw; flex: 0 0 92vw; max-height: calc(100vh - 64px - 190px); }
        .kb-list-placeholder { width: 92vw; flex: 0 0 92vw; }

        .kb-btn {
            padding: 10px 12px;
            font-size: 13px;
        }
        .kb-input {
            padding: 10px 12px;
            font-size: 14px;
        }
    }

    @media (max-width: 420px) {
        .kb-list { width: 94vw; flex: 0 0 94vw; }
        .kb-list-placeholder { width: 94vw; flex: 0 0 94vw; }
        .kb-board { padding: 10px; }
    }

    .kb-card.is-done {
        opacity: 0.78;
    }

    .kb-card-done-btn {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid var(--border-subtle);
        background: rgba(255,255,255,0.04);
        color: var(--text-secondary);
        cursor: pointer;
    }

    .kb-card.is-done .kb-card-done-btn {
        background: rgba(100, 210, 120, 0.18);
        border-color: rgba(100, 210, 120, 0.35);
        color: rgba(140, 240, 160, 1);
    }

    .kb-card-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        margin-top: 8px;
    }

    .kb-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.04);
        color: var(--text-secondary);
        line-height: 1.2;
    }

    .kb-badge--due-soon {
        background: rgba(255, 200, 90, 0.16);
        border-color: rgba(255, 200, 90, 0.28);
        color: rgba(255, 220, 160, 1);
    }

    .kb-badge--due-ok {
        background: rgba(100, 210, 120, 0.14);
        border-color: rgba(100, 210, 120, 0.26);
        color: rgba(160, 240, 180, 1);
    }

    .kb-badge--due-late {
        background: rgba(255, 70, 70, 0.16);
        border-color: rgba(255, 70, 70, 0.28);
        color: rgba(255, 150, 150, 1);
    }
</style>

<div class="kb-shell">
    <aside class="kb-sidebar">
        <div class="kb-sidebar-head">
            <div class="kb-sidebar-title">Kanban</div>
            <div style="display:flex; gap:8px; align-items:center;">
                <button type="button" class="kb-btn kb-toggle-sidebar" id="kb-toggle-sidebar" title="Minimizar painel">❮</button>
                <button type="button" class="kb-btn kb-btn--primary" id="kb-new-board">+ Quadro</button>
            </div>
        </div>
        <div class="kb-sidebar-list" id="kb-board-list">
            <?php if (empty($boards)): ?>
                <div style="padding:10px; color:var(--text-secondary); font-size:12px;">Você ainda não tem quadros. Clique em <b>+ Quadro</b>.</div>
            <?php else: ?>
                <?php foreach ($boards as $b): ?>
                    <?php
                        $bid = (int)($b['id'] ?? 0);
                        $active = $currentBoardId === $bid;
                        $bt = (string)($b['title'] ?? 'Sem título');
                    ?>
                    <a class="kb-board-item<?= $active ? ' is-active' : '' ?>" href="/kanban?board_id=<?= $bid ?>" data-board-id="<?= $bid ?>">
                        <span class="kb-board-item-title"><?= htmlspecialchars($bt) ?></span>
                        <span style="color:var(--text-secondary); font-size:12px;">›</span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <main class="kb-main">
        <div class="kb-main-head">
            <div class="kb-main-head-left">
                <button type="button" class="kb-btn kb-toggle-sidebar" id="kb-toggle-sidebar-alt" title="Mostrar/ocultar painel">☰</button>
                <div class="kb-main-title" id="kb-board-title"><?= htmlspecialchars($currentBoardTitle) ?></div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <?php if ($currentBoardId > 0): ?>
                    <?php $isOwner = !empty($currentBoard) && (int)($currentBoard['owner_user_id'] ?? 0) === (int)($user['id'] ?? 0); ?>
                    <?php if (!empty($canShareKanban) && ($isOwner || !empty($_SESSION['is_admin']))): ?>
                        <button type="button" class="kb-btn" id="kb-share-board">Compartilhar</button>
                    <?php endif; ?>
                    <button type="button" class="kb-btn" id="kb-rename-board">Renomear</button>
                    <button type="button" class="kb-btn kb-btn--danger" id="kb-delete-board">Excluir</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($currentBoardId <= 0): ?>
            <div style="padding:16px; color:var(--text-secondary); font-size:13px;">Crie um quadro para começar.</div>
        <?php else: ?>
            <div class="kb-board" id="kb-board" data-board-id="<?= $currentBoardId ?>">
                <?php foreach ($lists as $l): ?>
                    <?php
                        $lid = (int)($l['id'] ?? 0);
                        $lt = (string)($l['title'] ?? 'Sem título');
                        $cards = $cardsByList[$lid] ?? [];
                    ?>
                    <section class="kb-list" draggable="true" data-list-id="<?= $lid ?>">
                        <div class="kb-list-head">
                            <div class="kb-list-title" data-action="rename-list" data-list-id="<?= $lid ?>"><?= htmlspecialchars($lt) ?></div>
                            <div class="kb-list-actions">
                                <button type="button" class="kb-btn" data-action="add-card" data-list-id="<?= $lid ?>">+ Cartão</button>
                                <button type="button" class="kb-btn kb-btn--danger" data-action="delete-list" data-list-id="<?= $lid ?>">×</button>
                            </div>
                        </div>
                        <div class="kb-cards" data-cards-list-id="<?= $lid ?>">
                            <?php foreach ($cards as $c): ?>
                                <?php
                                    $cid = (int)($c['id'] ?? 0);
                                    $ct = (string)($c['title'] ?? 'Sem título');
                                    $cd = (string)($c['description'] ?? '');
                                    $due = (string)($c['due_date'] ?? '');
                                    $coverUrl = (string)($c['cover_url'] ?? '');
                                    $isDone = !empty($c['is_done']) && (string)$c['is_done'] !== '0';
                                    $attachmentsCount = (int)($c['attachments_count'] ?? 0);
                                    $chkTotal = (int)($c['checklist_total'] ?? 0);
                                    $chkDone = (int)($c['checklist_done'] ?? 0);

                                    $dueClass = '';
                                    $dueLabel = '';
                                    if ($due !== '') {
                                        $ts = strtotime($due);
                                        if ($ts !== false) {
                                            $today = strtotime(date('Y-m-d'));
                                            if ($ts < $today) {
                                                $dueClass = 'kb-badge--due-late';
                                            } elseif ($ts === $today) {
                                                $dueClass = 'kb-badge--due-soon';
                                            } else {
                                                $dueClass = 'kb-badge--due-ok';
                                            }
                                            $m = (int)date('n', $ts);
                                            $months = [1=>'jan.',2=>'fev.',3=>'mar.',4=>'abr.',5=>'mai.',6=>'jun.',7=>'jul.',8=>'ago.',9=>'set.',10=>'out.',11=>'nov.',12=>'dez.'];
                                            $dueLabel = (int)date('j', $ts) . ' de ' . ($months[$m] ?? date('M', $ts)) . ' de ' . date('Y', $ts);
                                        } else {
                                            $dueLabel = $due;
                                        }
                                    }
                                ?>
                                <div class="kb-card<?= $isDone ? ' is-done' : '' ?>" draggable="true" data-card-id="<?= $cid ?>" data-list-id="<?= $lid ?>" data-due-date="<?= htmlspecialchars($due) ?>" data-cover-url="<?= htmlspecialchars($coverUrl) ?>" data-attachments-count="<?= $attachmentsCount ?>" data-checklist-done="<?= $chkDone ?>" data-checklist-total="<?= $chkTotal ?>" data-is-done="<?= $isDone ? '1' : '0' ?>">
                                    <?php if ($coverUrl !== ''): ?>
                                        <div class="kb-card-cover" style="margin:-10px -10px 10px -10px; border-radius:12px; overflow:hidden;">
                                            <img src="<?= htmlspecialchars($coverUrl) ?>" alt="Capa" style="width:100%; height:140px; object-fit:cover; display:block;">
                                        </div>
                                    <?php endif; ?>
                                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px;">
                                        <div class="kb-card-title" style="flex:1; min-width:0;">
                                            <?= htmlspecialchars($ct) ?>
                                        </div>
                                        <button type="button" class="kb-card-done-btn" data-action="toggle-done" title="Marcar como concluído">
                                            <?= $isDone ? '✓' : '○' ?>
                                        </button>
                                    </div>

                                    <?php if ($dueLabel !== '' || $attachmentsCount > 0 || $chkTotal > 0): ?>
                                        <div class="kb-card-badges">
                                            <?php if ($dueLabel !== ''): ?>
                                                <span class="kb-badge <?= $dueClass ?>" data-badge="due" data-due-raw="<?= htmlspecialchars($due) ?>">
                                                    <span style="opacity:0.9;">⏰</span>
                                                    <span><?= htmlspecialchars($dueLabel) ?></span>
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($attachmentsCount > 0): ?>
                                                <span class="kb-badge" data-badge="attachments">
                                                    <span style="opacity:0.9;">📎</span>
                                                    <span><?= (int)$attachmentsCount ?></span>
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($chkTotal > 0): ?>
                                                <span class="kb-badge" data-badge="checklist">
                                                    <span style="opacity:0.9;">☑</span>
                                                    <span><?= (int)$chkDone ?>/<?= (int)$chkTotal ?></span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($cd !== ''): ?>
                                        <div class="kb-card-desc"><?= htmlspecialchars($cd) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>

                <section class="kb-list" id="kb-add-list-section" style="background:transparent; border:1px dashed var(--border-subtle); box-shadow:none;">
                    <div style="font-size:12px; color:var(--text-secondary); font-weight:700; margin-bottom:8px;">Adicionar lista</div>
                    <input class="kb-input" id="kb-new-list-title" placeholder="Ex.: A fazer" />
                    <button type="button" class="kb-btn kb-btn--primary" id="kb-add-list">Adicionar</button>
                </section>
            </div>
        <?php endif; ?>
    </main>
</div>

<div class="kb-modal" id="kb-modal">
    <div class="kb-modal-backdrop" id="kb-modal-backdrop"></div>
    <div class="kb-modal-card">
        <div class="kb-modal-title" id="kb-modal-title">Editar</div>

        <div class="kb-modal-two-col">
            <div class="kb-modal-left">
                <div id="kb-move-row" style="display:none;">
                    <div class="kb-modal-field-label">Lista</div>
                    <select class="kb-select" id="kb-move-list"></select>
                </div>

                <div class="kb-cover-box" id="kb-cover-section" style="display:none;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                        <div class="kb-attachments-title">Capa do cartão</div>
                        <button type="button" class="kb-btn" id="kb-cover-clear" style="display:none;">Remover capa</button>
                    </div>
                    <div class="kb-cover-preview" id="kb-cover-preview">
                        <div class="kb-cover-empty">Sem capa.</div>
                    </div>
                    <div class="kb-attachments-row" style="margin-top:10px;">
                        <input type="file" accept="image/*" class="kb-input" id="kb-cover-file" style="flex:1; min-width: 220px;" />
                        <button type="button" class="kb-btn kb-btn--primary" id="kb-cover-upload">Enviar capa</button>
                    </div>
                    <div style="color:var(--text-secondary); font-size:12px; padding:8px 2px;">Apenas 1 capa por cartão. Para trocar, remova e envie outra.</div>
                </div>

                <div style="display:flex; flex-direction:column; gap:8px;">
                    <input class="kb-input" id="kb-modal-input" placeholder="Título" />
                    <textarea class="kb-input" id="kb-modal-textarea" placeholder="Descrição" style="min-height:110px; resize:vertical;"></textarea>
                </div>

                <div id="kb-due-row" style="display:flex; flex-direction:column; gap:6px;">
                    <div class="kb-modal-field-label">Data de entrega</div>
                    <input type="date" class="kb-input" id="kb-modal-due-date" />
                </div>

                <div class="kb-attachments" id="kb-checklist" style="display:none;">
                    <div class="kb-attachments-title">Checklist</div>
                    <div class="kb-attachments-row">
                        <input class="kb-input" id="kb-checklist-new" placeholder="Adicionar item..." style="flex:1; min-width: 220px;" />
                        <button type="button" class="kb-btn kb-btn--primary" id="kb-checklist-add">Adicionar</button>
                    </div>
                    <div class="kb-attachments-list" id="kb-checklist-list"></div>
                </div>

                <div class="kb-attachments" id="kb-attachments" style="display:none;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                        <div class="kb-attachments-title">Anexos</div>
                    </div>
                    <div class="kb-attachments-row">
                        <input type="file" class="kb-input" id="kb-attach-file" style="flex:1; min-width: 220px;" />
                        <button type="button" class="kb-btn kb-btn--primary" id="kb-attach-upload">Enviar</button>
                    </div>
                    <div class="kb-attachments-list" id="kb-attach-list"></div>
                </div>
            </div>

            <div class="kb-modal-right" id="kb-modal-right">
                <div class="kb-modal-side-title">Ações</div>
                <button type="button" class="kb-btn kb-qa-btn" id="kb-qa-checklist">☑ Checklist</button>
                <button type="button" class="kb-btn kb-qa-btn" id="kb-qa-attachments">📎 Anexos</button>
                <button type="button" class="kb-btn kb-qa-btn" id="kb-qa-cover">🖼 Capa</button>
                <button type="button" class="kb-btn kb-qa-btn" id="kb-qa-move">⇄ Mover</button>
            </div>
        </div>

        <div class="kb-modal-actions kb-modal-actions--footer">
            <button type="button" class="kb-btn" id="kb-modal-cancel">Cancelar</button>
            <button type="button" class="kb-btn kb-btn--danger" id="kb-modal-delete" style="display:none;">Excluir</button>
            <button type="button" class="kb-btn kb-btn--primary" id="kb-modal-save">Salvar</button>
        </div>
    </div>
</div>

<div class="kb-modal" id="kb-share-modal">
    <div class="kb-modal-backdrop" id="kb-share-backdrop"></div>
    <div class="kb-modal-card kb-share-card">
        <div class="kb-modal-title">Compartilhar quadro</div>
        <div style="color:var(--text-secondary); font-size:12px; margin-top:6px;">
            Adicione pessoas pelo e-mail para elas poderem acessar e interagir com este quadro.
        </div>

        <div class="kb-share-row">
            <input class="kb-input" id="kb-share-email" placeholder="email@exemplo.com" style="flex:1; min-width: 220px;" />
            <button type="button" class="kb-btn kb-btn--primary" id="kb-share-add">Adicionar</button>
        </div>

        <div class="kb-share-list" id="kb-share-list"></div>

        <div class="kb-modal-actions">
            <button type="button" class="kb-btn" id="kb-share-close">Fechar</button>
        </div>
    </div>
</div>

<div class="kb-preview-modal" id="kb-preview-modal" aria-hidden="true">
    <div class="kb-preview-backdrop" id="kb-preview-backdrop"></div>
    <div class="kb-preview-card" role="dialog" aria-modal="true" aria-label="Pré-visualização">
        <div class="kb-preview-head">
            <div class="kb-preview-title" id="kb-preview-title">Arquivo</div>
            <div style="display:flex; gap:8px; align-items:center;">
                <a class="kb-btn" id="kb-preview-download" href="#" download style="display:none;">Baixar</a>
                <button type="button" class="kb-btn" id="kb-preview-close">Fechar</button>
            </div>
        </div>
        <div class="kb-preview-body" id="kb-preview-body"></div>
    </div>
</div>

<script>
(function () {
    var boardId = <?= (int)$currentBoardId ?>;
    var canShareKanban = <?= !empty($canShareKanban) ? 'true' : 'false' ?>;

    var SIDEBAR_KEY = 'kanban.sidebarCollapsed';

    function $(id) { return document.getElementById(id); }

    function setSidebarCollapsed(collapsed) {
        if (collapsed) {
            document.body.classList.add('kb-sidebar-collapsed');
        } else {
            document.body.classList.remove('kb-sidebar-collapsed');
        }
        try {
            localStorage.setItem(SIDEBAR_KEY, collapsed ? '1' : '0');
        } catch (e) {}

        var btn = $('kb-toggle-sidebar');
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

    function postForm(url, data) {
        var fd = new FormData();
        Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
        return fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, status: r.status, json: j }; }); })
            .catch(function (e) { return { ok: false, status: 0, json: { ok: false, error: String(e || 'Erro') } }; });
    }

    function postFile(url, formData) {
        return fetch(url, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, status: r.status, json: j }; }); })
            .catch(function (e) { return { ok: false, status: 0, json: { ok: false, error: String(e || 'Erro') } }; });
    }

    function postSync(payload) {
        return postForm('/kanban/sync', { payload: JSON.stringify(payload || {}) });
    }

    function esc(s) {
        var div = document.createElement('div');
        div.textContent = String(s == null ? '' : s);
        return div.innerHTML;
    }

    function openPreviewModal(url, name, mime, attachmentId) {
        var modal = $('kb-preview-modal');
        var backdrop = $('kb-preview-backdrop');
        var closeBtn = $('kb-preview-close');
        var titleEl = $('kb-preview-title');
        var bodyEl = $('kb-preview-body');
        var downloadEl = $('kb-preview-download');
        if (!modal || !backdrop || !closeBtn || !titleEl || !bodyEl || !downloadEl) return;

        url = String(url || '');
        name = String(name || 'Arquivo');
        mime = String(mime || '');
        if (!url) return;

        titleEl.textContent = name || 'Arquivo';
        bodyEl.innerHTML = '';

        downloadEl.style.display = 'inline-flex';
        try { downloadEl.removeAttribute('download'); } catch (e) {}
        if (attachmentId) {
            downloadEl.href = '/kanban/cartao/anexos/download?attachment_id=' + encodeURIComponent(String(attachmentId));
        } else {
            downloadEl.href = url;
        }

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
            frame.className = 'kb-preview-frame';
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

    // (funções utilitárias acima)

    function createCardEl(card, listId) {
        var el = document.createElement('div');
        el.className = 'kb-card';
        el.setAttribute('draggable', 'true');
        el.setAttribute('data-card-id', String(card.id));
        el.setAttribute('data-list-id', String(listId));
        el.setAttribute('data-due-date', String(card.due_date || ''));
        el.setAttribute('data-cover-url', String(card.cover_url || ''));
        el.setAttribute('data-attachments-count', String(card.attachments_count || '0'));
        el.setAttribute('data-checklist-done', String(card.checklist_done || '0'));
        el.setAttribute('data-checklist-total', String(card.checklist_total || '0'));
        el.setAttribute('data-is-done', String(card.is_done || '0'));
        if (String(card.is_done || '0') !== '0') {
            el.classList.add('is-done');
        }

        el.innerHTML = '';
        if (card.cover_url) {
            el.innerHTML += '<div class="kb-card-cover" style="margin:-10px -10px 10px -10px; border-radius:12px; overflow:hidden;">'
                + '<img src="' + esc(card.cover_url) + '" alt="Capa" style="width:100%; height:140px; object-fit:cover; display:block;">'
                + '</div>';
        }
        el.innerHTML += '<div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px;">'
            + '<div class="kb-card-title" style="flex:1; min-width:0;">' + esc(card.title || 'Sem título') + '</div>'
            + '<button type="button" class="kb-card-done-btn" data-action="toggle-done" title="Marcar como concluído">' + (String(card.is_done || '0') !== '0' ? '✓' : '○') + '</button>'
            + '</div>';

        var badges = [];
        var dueRaw = String(card.due_date || '');
        var dueInfo = dueRaw ? getDueBadgeInfo(dueRaw) : null;
        if (dueInfo) {
            badges.push('<span class="kb-badge ' + esc(dueInfo.cls) + '" data-badge="due" data-due-raw="' + esc(dueRaw) + '">' + '<span style="opacity:0.9;">⏰</span>' + '<span>' + esc(dueInfo.label) + '</span></span>');
        }
        var attCount = parseInt(String(card.attachments_count || '0'), 10) || 0;
        if (attCount > 0) {
            badges.push('<span class="kb-badge" data-badge="attachments"><span style="opacity:0.9;">📎</span><span>' + esc(String(attCount)) + '</span></span>');
        }
        var chkTotal = parseInt(String(card.checklist_total || '0'), 10) || 0;
        var chkDone = parseInt(String(card.checklist_done || '0'), 10) || 0;
        if (chkTotal > 0) {
            badges.push('<span class="kb-badge" data-badge="checklist"><span style="opacity:0.9;">☑</span><span>' + esc(String(chkDone)) + '/' + esc(String(chkTotal)) + '</span></span>');
        }
        if (badges.length) {
            el.innerHTML += '<div class="kb-card-badges">' + badges.join('') + '</div>';
        }

        if (card.description) {
            el.innerHTML += '<div class="kb-card-desc">' + esc(card.description) + '</div>';
        }
        return el;
    }

    function getDueBadgeInfo(dateStr) {
        if (!dateStr) return null;
        var m = String(dateStr).match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!m) {
            return { cls: 'kb-badge--due-ok', label: String(dateStr) };
        }
        var y = parseInt(m[1], 10);
        var mo = parseInt(m[2], 10);
        var d = parseInt(m[3], 10);

        var dt = new Date(Date.UTC(y, mo - 1, d));
        var now = new Date();
        var today = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate()));

        var cls = 'kb-badge--due-ok';
        if (dt.getTime() < today.getTime()) cls = 'kb-badge--due-late';
        else if (dt.getTime() === today.getTime()) cls = 'kb-badge--due-soon';

        var months = ['jan.', 'fev.', 'mar.', 'abr.', 'mai.', 'jun.', 'jul.', 'ago.', 'set.', 'out.', 'nov.', 'dez.'];
        var label = d + ' de ' + (months[mo - 1] || '') + ' de ' + y;
        return { cls: cls, label: label };
    }

    function ensureListTitleClickIsNotDnd(el) {
        // no-op (mantém compatibilidade com delegation)
    }

    function createListEl(listId, title) {
        var sec = document.createElement('section');
        sec.className = 'kb-list';
        sec.setAttribute('draggable', 'true');
        sec.setAttribute('data-list-id', String(listId));

        sec.innerHTML = ''
            + '<div class="kb-list-head">'
            + '  <div class="kb-list-title" data-action="rename-list" data-list-id="' + String(listId) + '">' + esc(title || 'Sem título') + '</div>'
            + '  <div class="kb-list-actions">'
            + '    <button type="button" class="kb-btn" data-action="add-card" data-list-id="' + String(listId) + '">+ Cartão</button>'
            + '    <button type="button" class="kb-btn kb-btn--danger" data-action="delete-list" data-list-id="' + String(listId) + '">×</button>'
            + '  </div>'
            + '</div>'
            + '<div class="kb-cards" data-cards-list-id="' + String(listId) + '"></div>';

        ensureListTitleClickIsNotDnd(sec);
        return sec;
    }

    function getAddListSection() {
        return $('kb-add-list-section');
    }

    function getBoardEl() {
        return $('kb-board');
    }

    function getListEl(listId) {
        return document.querySelector('.kb-list[data-list-id="' + String(listId) + '"]');
    }

    function getCardEl(cardId) {
        return document.querySelector('.kb-card[data-card-id="' + String(cardId) + '"]');
    }

    function getCardsContainer(listId) {
        return document.querySelector('.kb-cards[data-cards-list-id="' + String(listId) + '"]');
    }

    function ensureCardPlaceholderHeight(ph, referenceEl) {
        if (!ph) return;
        if (referenceEl && referenceEl.getBoundingClientRect) {
            var r = referenceEl.getBoundingClientRect();
            if (r && r.height) {
                ph.style.height = Math.max(40, Math.floor(r.height)) + 'px';
            }
        }
    }

    function createCardPlaceholder(referenceEl) {
        var ph = document.createElement('div');
        ph.className = 'kb-card-placeholder';
        ensureCardPlaceholderHeight(ph, referenceEl);
        return ph;
    }

    function createListPlaceholder() {
        var ph = document.createElement('div');
        ph.className = 'kb-list-placeholder';
        return ph;
    }

    function openModal(opts) {
        var modal = $('kb-modal');
        if (!modal) return;
        modal.classList.add('is-open');
        $('kb-modal-title').textContent = opts.title || 'Editar';
        $('kb-modal-input').value = String(opts.value || '').trim();
        $('kb-modal-textarea').value = opts.desc || '';
        var dd = $('kb-modal-due-date');
        if (dd) dd.value = opts.dueDate || '';
        $('kb-modal-textarea').style.display = opts.showDesc ? 'block' : 'none';

        var isEditCard = (opts.mode === 'edit-card' && opts.cardId);

        var right = $('kb-modal-right');
        if (right) {
            right.style.display = isEditCard ? 'flex' : 'none';
        }

        var dueRow = $('kb-due-row');
        if (dueRow) {
            dueRow.style.display = isEditCard ? 'flex' : 'none';
        }

        var del = $('kb-modal-delete');
        if (del) del.style.display = opts.showDelete ? 'inline-flex' : 'none';
        var mr = $('kb-move-row');
        if (mr) mr.style.display = opts.showMove ? 'block' : 'none';

        var coverSection = $('kb-cover-section');
        if (coverSection) {
            coverSection.style.display = (opts.mode === 'edit-card' && opts.cardId) ? 'block' : 'none';
        }

        var chk = $('kb-checklist');
        if (chk) {
            chk.style.display = isEditCard ? (chk.style.display || 'none') : 'none';
        }
        var att = $('kb-attachments');
        if (att) {
            att.style.display = isEditCard ? (att.style.display || 'none') : 'none';
        }

        modal.dataset.mode = opts.mode || '';
        modal.dataset.cardId = String(opts.cardId || '');
        modal.dataset.listId = String(opts.listId || '');
        modal.dataset.coverUrl = String(opts.coverUrl || '');
        modal.dataset.boardId = String(opts.boardId || '');

        if (del) {
            del.onclick = null;
            if (opts.onDelete) {
                del.onclick = opts.onDelete;
            }
        }

        $('kb-modal-save').onclick = null;
        $('kb-modal-save').onclick = function () {
            if (opts.onSave) {
                opts.onSave($('kb-modal-input').value, $('kb-modal-textarea').value);
            }
        };
    }

    function closeModal() {
        var modal = $('kb-modal');
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.dataset.mode = '';
        modal.dataset.cardId = '';
        modal.dataset.listId = '';
        modal.dataset.coverUrl = '';
        modal.dataset.boardId = '';

        var moveRow = $('kb-move-row');
        if (moveRow) moveRow.style.display = 'none';
        var moveSel = $('kb-move-list');
        if (moveSel) moveSel.innerHTML = '';

        var att = $('kb-attachments');
        if (att) att.style.display = 'none';
        var chk = $('kb-checklist');
        if (chk) chk.style.display = 'none';
        var file = $('kb-attach-file');
        if (file) file.value = '';
        var list = $('kb-attach-list');
        if (list) list.innerHTML = '';
        var cl = $('kb-checklist-list');
        if (cl) cl.innerHTML = '';
        var ci = $('kb-checklist-new');
        if (ci) ci.value = '';

        var coverClear = $('kb-cover-clear');
        if (coverClear) {
            coverClear.style.display = 'none';
            coverClear.onclick = null;
        }

        var coverSection = $('kb-cover-section');
        if (coverSection) coverSection.style.display = 'none';
        var coverPrev = $('kb-cover-preview');
        if (coverPrev) coverPrev.innerHTML = '<div class="kb-cover-empty">Sem capa.</div>';
        var coverFile = $('kb-cover-file');
        if (coverFile) coverFile.value = '';
    }

    function buildListOptions(selectEl, selectedListId) {
        if (!selectEl) return;
        selectEl.innerHTML = '';
        var lists = document.querySelectorAll('.kb-list[data-list-id]');
        for (var i = 0; i < lists.length; i++) {
            var lid = lists[i].getAttribute('data-list-id');
            if (!lid) continue;
            var titleEl = lists[i].querySelector('.kb-list-title');
            var label = titleEl ? (titleEl.textContent || '') : ('Lista ' + lid);
            var opt = document.createElement('option');
            opt.value = String(lid);
            opt.textContent = label.trim() || ('Lista ' + lid);
            if (String(lid) === String(selectedListId)) {
                opt.selected = true;
            }
            selectEl.appendChild(opt);
        }
    }

    function moveCardToList(cardId, fromListId, toListId) {
        var cardEl = getCardEl(cardId);
        if (!cardEl) return;

        var fromContainer = getCardsContainer(fromListId);
        var toContainer = getCardsContainer(toListId);
        if (!toContainer) return;

        // Move visual: coloca no final da lista destino
        if (cardEl.parentNode) {
            cardEl.parentNode.removeChild(cardEl);
        }
        toContainer.appendChild(cardEl);
        cardEl.setAttribute('data-list-id', String(toListId));

        var payload = { board_id: boardId, cards_by_list: {} };
        payload.cards_by_list[String(toListId)] = serializeCardOrder(toListId);
        if (fromListId && String(fromListId) !== String(toListId)) {
            payload.cards_by_list[String(fromListId)] = serializeCardOrder(fromListId);
        }
        postSync(payload);
    }

    function renderAttachments(items) {
        var list = $('kb-attach-list');
        if (!list) return;
        list.innerHTML = '';

        if (!items || !items.length) {
            list.innerHTML = '<div style="color:var(--text-secondary); font-size:12px; padding:6px 2px;">Nenhum anexo.</div>';
            return;
        }

        items.forEach(function (a) {
            var id = a && a.id ? String(a.id) : '';
            var url = a && a.url ? String(a.url) : '';
            var name = (a && a.original_name ? String(a.original_name) : (url ? url.split('/').pop() : 'Arquivo'));
            var mime = a && a.mime_type ? String(a.mime_type) : '';
            var isImage = mime && mime.toLowerCase().indexOf('image/') === 0;

            var row = document.createElement('div');
            row.className = 'kb-attachment-item';
            row.setAttribute('data-attachment-id', id);

            var left = document.createElement('div');
            left.className = 'kb-attachment-left';
            left.innerHTML = '<div class="kb-attachment-name">' + esc(name) + '</div>';

            var actions = document.createElement('div');
            actions.className = 'kb-attachment-actions';
            actions.innerHTML = ''
                + '<button type="button" class="kb-btn" data-action="preview-attachment" data-attachment-id="' + esc(id) + '" data-url="' + esc(url) + '" data-name="' + esc(name) + '" data-mime="' + esc(mime) + '">Abrir</button>'
                + '<button type="button" class="kb-btn" data-action="download-attachment" data-attachment-id="' + esc(id) + '">Baixar</button>'
                + '<button type="button" class="kb-btn kb-btn--danger" data-action="delete-attachment" data-attachment-id="' + esc(id) + '">Remover</button>';

            row.appendChild(left);
            row.appendChild(actions);
            list.appendChild(row);
        });
    }

    function loadAttachments(cardId) {
        return postForm('/kanban/cartao/anexos/listar', { card_id: String(cardId) }).then(function (res) {
            if (res.json && res.json.ok) {
                renderAttachments(res.json.attachments || []);
                return;
            }
            renderAttachments([]);
        });
    }

    // Sidebar collapse init
    setSidebarCollapsed(getSidebarCollapsed());
    var toggleBtn = $('kb-toggle-sidebar');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            setSidebarCollapsed(!document.body.classList.contains('kb-sidebar-collapsed'));
        });
    }

    var toggleBtnAlt = $('kb-toggle-sidebar-alt');
    if (toggleBtnAlt) {
        toggleBtnAlt.addEventListener('click', function () {
            setSidebarCollapsed(!document.body.classList.contains('kb-sidebar-collapsed'));
        });
    }

    document.addEventListener('click', function (e) {
        var t = e.target;
        if (!t) return;
        var act = t.getAttribute && t.getAttribute('data-action');
        if (act === 'preview-attachment') {
            e.preventDefault();
            var url = t.getAttribute('data-url');
            var name = t.getAttribute('data-name');
            var mime = t.getAttribute('data-mime');
            var attId = t.getAttribute('data-attachment-id');
            openPreviewModal(url, name, mime, attId);
            return;
        }
        if (act === 'download-attachment') {
            e.preventDefault();
            var attId = t.getAttribute('data-attachment-id');
            if (!attId) return;
            window.location.href = '/kanban/cartao/anexos/download?attachment_id=' + encodeURIComponent(String(attId));
            return;
        }
        if (act === 'delete-attachment') {
            var attId = t.getAttribute('data-attachment-id');
            if (!attId) return;
            if (!confirm('Remover este anexo?')) return;
            postForm('/kanban/cartao/anexos/excluir', { attachment_id: String(attId) }).then(function (res) {
                if (res.json && res.json.ok) {
                    var row = document.querySelector('.kb-attachment-item[data-attachment-id="' + String(attId) + '"]');
                    if (row && row.parentNode) row.parentNode.removeChild(row);
                } else {
                    alert((res.json && res.json.error) ? res.json.error : 'Falha ao remover anexo.');
                }
            });
        }
    });

    function renderChecklist(items) {
        var list = $('kb-checklist-list');
        if (!list) return;
        list.innerHTML = '';
        if (!items || !items.length) {
            list.innerHTML = '<div style="color:var(--text-secondary); font-size:12px; padding:6px 2px;">Nenhum item.</div>';
            return;
        }
        items.forEach(function (it) {
            var row = document.createElement('div');
            row.className = 'kb-attachment-item';
            row.setAttribute('data-checklist-id', String(it.id || ''));
            var left = document.createElement('div');
            left.className = 'kb-attachment-left';
            var done = !!(it.is_done && String(it.is_done) !== '0');
            left.innerHTML = '<label style="display:flex; align-items:center; gap:8px;">'
                + '<input type="checkbox" data-action="toggle-check" data-item-id="' + esc(String(it.id || '')) + '" ' + (done ? 'checked' : '') + '>'
                + '<span style="' + (done ? 'text-decoration:line-through; opacity:0.7;' : '') + '">' + esc(String(it.content || '')) + '</span>'
                + '</label>';
            var actions = document.createElement('div');
            actions.className = 'kb-attachment-actions';
            actions.innerHTML = '<button type="button" class="kb-btn kb-btn--danger" data-action="delete-check" data-item-id="' + esc(String(it.id || '')) + '">Remover</button>';
            row.appendChild(left);
            row.appendChild(actions);
            list.appendChild(row);
        });
    }

    function loadChecklist(cardId) {
        return postForm('/kanban/cartao/checklist/listar', { card_id: String(cardId) }).then(function (res) {
            if (res.json && res.json.ok) {
                renderChecklist(res.json.items || []);
                return;
            }
            renderChecklist([]);
        });
    }

    document.addEventListener('click', function (e) {
        var t = e.target;
        if (!t) return;
        var act = t.getAttribute && t.getAttribute('data-action');
        if (act === 'delete-check') {
            var itemId = t.getAttribute('data-item-id');
            if (!itemId) return;
            postForm('/kanban/cartao/checklist/excluir', { item_id: String(itemId) }).then(function (res) {
                if (res.json && res.json.ok) {
                    var modal = $('kb-modal');
                    if (!modal) return;
                    var cardId = modal.dataset.cardId;
                    if (!cardId) return;
                    loadChecklist(cardId);
                } else {
                    alert((res.json && res.json.error) ? res.json.error : 'Falha ao remover item.');
                }
            });
        }
    });

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t) return;
        var act = t.getAttribute && t.getAttribute('data-action');
        if (act === 'toggle-check') {
            var itemId = t.getAttribute('data-item-id');
            if (!itemId) return;
            postForm('/kanban/cartao/checklist/toggle', { item_id: String(itemId), done: t.checked ? '1' : '0' });
        }
    });

    var addCheckBtn = $('kb-checklist-add');
    if (addCheckBtn) {
        addCheckBtn.addEventListener('click', function () {
            var modal = $('kb-modal');
            if (!modal) return;
            var cardId = modal.dataset.cardId;
            if (!cardId) return;
            var input = $('kb-checklist-new');
            var content = input ? input.value : '';
            postForm('/kanban/cartao/checklist/adicionar', { card_id: String(cardId), content: content || '' }).then(function (res) {
                if (res.json && res.json.ok) {
                    if (input) input.value = '';
                    loadChecklist(cardId);
                } else {
                    alert((res.json && res.json.error) ? res.json.error : 'Falha ao adicionar item.');
                }
            });
        });
    }

    var uploadBtn = $('kb-attach-upload');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function () {
            var modal = $('kb-modal');
            if (!modal) return;
            var cardId = modal.dataset.cardId;
            if (!cardId) return;
            var input = $('kb-attach-file');
            if (!input || !input.files || !input.files[0]) {
                alert('Selecione um arquivo.');
                return;
            }
            var fd = new FormData();
            fd.append('card_id', String(cardId));
            fd.append('file', input.files[0]);
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Enviando...';
            postFile('/kanban/cartao/anexos/upload', fd).then(function (res) {
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Enviar';
                if (res.json && res.json.ok && res.json.attachment) {
                    input.value = '';
                    loadAttachments(cardId);
                } else {
                    alert((res.json && res.json.error) ? res.json.error : 'Falha ao enviar anexo.');
                }
            });
        });
    }

    var backdrop = $('kb-modal-backdrop');
    if (backdrop) backdrop.addEventListener('click', closeModal);
    var cancel = $('kb-modal-cancel');
    if (cancel) cancel.addEventListener('click', closeModal);

    function scrollIntoModalView(el) {
        if (!el) return;
        try {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (e) {
            el.scrollIntoView(true);
        }
    }

    var qaChecklist = $('kb-qa-checklist');
    if (qaChecklist) {
        qaChecklist.addEventListener('click', function () {
            var modal = $('kb-modal');
            if (!modal) return;
            var cardId = modal.dataset.cardId;
            if (!cardId) return;
            var chk = $('kb-checklist');
            if (chk) chk.style.display = 'block';
            loadChecklist(cardId);
            scrollIntoModalView(chk);
            var input = $('kb-checklist-new');
            if (input) input.focus();
        });
    }

    var qaAttachments = $('kb-qa-attachments');
    if (qaAttachments) {
        qaAttachments.addEventListener('click', function () {
            var modal = $('kb-modal');
            if (!modal) return;
            var cardId = modal.dataset.cardId;
            if (!cardId) return;
            var att = $('kb-attachments');
            if (att) att.style.display = 'block';
            loadAttachments(cardId);
            scrollIntoModalView(att);
            var input = $('kb-attach-file');
            if (input) input.focus();
        });
    }

    var qaCover = $('kb-qa-cover');
    if (qaCover) {
        qaCover.addEventListener('click', function () {
            var modal = $('kb-modal');
            if (!modal) return;
            var cardId = modal.dataset.cardId;
            if (!cardId) return;
            var cover = $('kb-cover-section');
            if (cover) {
                cover.style.display = 'block';
                scrollIntoModalView(cover);
            }
        });
    }

    var qaMove = $('kb-qa-move');
    if (qaMove) {
        qaMove.addEventListener('click', function () {
            var mr = $('kb-move-row');
            if (!mr) return;
            mr.style.display = 'block';
            scrollIntoModalView(mr);
            var sel = $('kb-move-list');
            if (sel) sel.focus();
        });
    }

    function updateCardCoverVisual(cardId, coverUrl) {
        var cardEl = getCardEl(cardId);
        if (!cardEl) return;
        cardEl.setAttribute('data-cover-url', coverUrl || '');
        var coverBox = cardEl.querySelector('.kb-card-cover');
        if (!coverUrl) {
            if (coverBox && coverBox.parentNode) coverBox.parentNode.removeChild(coverBox);
            return;
        }
        if (!coverBox) {
            coverBox = document.createElement('div');
            coverBox.className = 'kb-card-cover';
            coverBox.style.margin = '-10px -10px 10px -10px';
            coverBox.style.borderRadius = '12px';
            coverBox.style.overflow = 'hidden';
            cardEl.insertBefore(coverBox, cardEl.firstChild);
        }
        coverBox.innerHTML = '<img src="' + esc(coverUrl) + '" alt="Capa" style="width:100%; height:140px; object-fit:cover; display:block;">';
    }

    function updateModalCoverVisual(coverUrl) {
        var modal = $('kb-modal');
        if (!modal) return;
        modal.dataset.coverUrl = String(coverUrl || '');
        var preview = $('kb-cover-preview');
        if (preview) {
            preview.innerHTML = coverUrl ? '<img src="' + esc(coverUrl) + '" alt="Capa">' : '<div class="kb-cover-empty">Sem capa.</div>';
        }
        var coverClear = $('kb-cover-clear');
        if (coverClear) coverClear.style.display = coverUrl ? 'inline-flex' : 'none';
    }

    var coverUploadBtn = $('kb-cover-upload');
    if (coverUploadBtn) {
        coverUploadBtn.addEventListener('click', function () {
            var modal = $('kb-modal');
            if (!modal) return;
            var cardId = modal.dataset.cardId;
            if (!cardId) return;
            var input = $('kb-cover-file');
            if (!input || !input.files || !input.files[0]) {
                alert('Escolha uma imagem para enviar.');
                return;
            }
            coverUploadBtn.disabled = true;
            coverUploadBtn.textContent = 'Enviando...';
            var fd = new FormData();
            fd.append('card_id', String(cardId));
            fd.append('file', input.files[0]);
            postFile('/kanban/cartao/capa/upload', fd).then(function (res) {
                coverUploadBtn.disabled = false;
                coverUploadBtn.textContent = 'Enviar capa';
                if (res.json && res.json.ok) {
                    var coverUrl = (res.json && res.json.cover_url) ? String(res.json.cover_url) : '';
                    updateModalCoverVisual(coverUrl);
                    updateCardCoverVisual(cardId, coverUrl);
                    if (input) input.value = '';
                } else {
                    alert((res.json && res.json.error) ? res.json.error : 'Falha ao enviar capa.');
                }
            });
        });
    }

    function openShareModal() {
        var modal = $('kb-share-modal');
        if (!modal) return;
        modal.classList.add('is-open');
        var email = $('kb-share-email');
        if (email) email.value = '';
        loadBoardMembers();
    }

    function closeShareModal() {
        var modal = $('kb-share-modal');
        if (!modal) return;
        modal.classList.remove('is-open');
        var list = $('kb-share-list');
        if (list) list.innerHTML = '';
        var email = $('kb-share-email');
        if (email) email.value = '';
    }

    function renderBoardMembers(members) {
        var list = $('kb-share-list');
        if (!list) return;
        list.innerHTML = '';

        if (!members || !members.length) {
            list.innerHTML = '<div class="kb-share-empty">Nenhum membro adicionado.</div>';
            return;
        }

        members.forEach(function (m) {
            var uid = m && m.user_id ? String(m.user_id) : '';
            var name = (m && (m.preferred_name || m.name)) ? String(m.preferred_name || m.name) : 'Usuário';
            var email = m && m.email ? String(m.email) : '';
            var row = document.createElement('div');
            row.className = 'kb-attachment-item';
            row.innerHTML = ''
                + '<div class="kb-attachment-left">'
                + '  <div class="kb-attachment-name">' + esc(name) + '</div>'
                + '  <div style="color:var(--text-secondary); font-size:12px;">' + esc(email) + '</div>'
                + '</div>'
                + '<div class="kb-attachment-actions">'
                + '  <button type="button" class="kb-btn kb-btn--danger" data-action="remove-board-member" data-user-id="' + esc(uid) + '">Remover</button>'
                + '</div>';
            list.appendChild(row);
        });
    }

    function loadBoardMembers() {
        if (!boardId) return;
        return postForm('/kanban/quadro/membros/listar', { board_id: String(boardId) }).then(function (res) {
            if (res.json && res.json.ok) {
                renderBoardMembers(res.json.members || []);
                return;
            }
            renderBoardMembers([]);
        });
    }

    var shareBtn = $('kb-share-board');
    if (shareBtn && boardId && canShareKanban) {
        shareBtn.addEventListener('click', openShareModal);
    }
    var shareBackdrop = $('kb-share-backdrop');
    if (shareBackdrop) shareBackdrop.addEventListener('click', closeShareModal);
    var shareClose = $('kb-share-close');
    if (shareClose) shareClose.addEventListener('click', closeShareModal);
    var shareAdd = $('kb-share-add');
    if (shareAdd) {
        shareAdd.addEventListener('click', function () {
            if (!boardId) return;
            var email = $('kb-share-email');
            var value = email ? String(email.value || '').trim() : '';
            if (!value) {
                alert('Informe o e-mail.');
                return;
            }
            shareAdd.disabled = true;
            postForm('/kanban/quadro/membros/adicionar', { board_id: String(boardId), email: value }).then(function (res) {
                shareAdd.disabled = false;
                if (res.json && res.json.ok) {
                    if (email) email.value = '';
                    loadBoardMembers();
                } else {
                    alert((res.json && res.json.error) ? res.json.error : 'Falha ao adicionar membro.');
                }
            });
        });
    }

    document.addEventListener('click', function (e) {
        var t = e.target;
        if (!t) return;
        var act = t.getAttribute && t.getAttribute('data-action');
        if (act === 'remove-board-member') {
            var uid = t.getAttribute('data-user-id');
            if (!uid) return;
            if (!confirm('Remover este membro do quadro?')) return;
            postForm('/kanban/quadro/membros/remover', { board_id: String(boardId), user_id: String(uid) }).then(function (res) {
                if (res.json && res.json.ok) {
                    loadBoardMembers();
                } else {
                    alert((res.json && res.json.error) ? res.json.error : 'Falha ao remover membro.');
                }
            });
        }
    });

    var btnNewBoard = $('kb-new-board');
    if (btnNewBoard) {
        btnNewBoard.addEventListener('click', function () {
            openModal({
                title: 'Novo quadro',
                value: '',
                showDesc: false,
                showDelete: false,
                onSave: function (title) {
                    postForm('/kanban/quadro/criar', { title: title || '' }).then(function (res) {
                        if (res.json && res.json.ok && res.json.board_id) {
                            window.location.href = '/kanban?board_id=' + encodeURIComponent(String(res.json.board_id));
                        } else {
                            alert((res.json && res.json.error) ? res.json.error : 'Falha ao criar quadro.');
                        }
                    });
                }
            });
        });
    }

    var btnRenameBoard = $('kb-rename-board');
    if (btnRenameBoard && boardId) {
        btnRenameBoard.addEventListener('click', function () {
            openModal({
                title: 'Renomear quadro',
                value: $('kb-board-title') ? $('kb-board-title').textContent : '',
                showDesc: false,
                showDelete: false,
                onSave: function (title) {
                    postForm('/kanban/quadro/renomear', { board_id: String(boardId), title: title || '' }).then(function (res) {
                        if (res.json && res.json.ok) {
                            var tEl = $('kb-board-title');
                            if (tEl) tEl.textContent = title || 'Sem título';
                            // atualiza item na sidebar
                            var side = document.querySelector('.kb-board-item.is-active .kb-board-item-title');
                            if (side) side.textContent = title || 'Sem título';
                            closeModal();
                        } else {
                            alert((res.json && res.json.error) ? res.json.error : 'Falha ao renomear.');
                        }
                    });
                }
            });
        });
    }

    var btnDeleteBoard = $('kb-delete-board');
    if (btnDeleteBoard && boardId) {
        btnDeleteBoard.addEventListener('click', function () {
            if (!confirm('Excluir este quadro?')) return;
            postForm('/kanban/quadro/excluir', { board_id: String(boardId) }).then(function (res) {
                if (res.json && res.json.ok) {
                    window.location.href = '/kanban';
                } else {
                    alert((res.json && res.json.error) ? res.json.error : 'Falha ao excluir.');
                }
            });
        });
    }

    var btnAddList = $('kb-add-list');
    if (btnAddList && boardId) {
        btnAddList.addEventListener('click', function () {
            var input = $('kb-new-list-title');
            var title = input ? input.value : '';
            postForm('/kanban/lista/criar', { board_id: String(boardId), title: title || '' }).then(function (res) {
                if (res.json && res.json.ok && res.json.list_id) {
                    var boardEl = getBoardEl();
                    var addSec = getAddListSection();
                    if (boardEl && addSec) {
                        var newListEl = createListEl(res.json.list_id, title || 'Sem título');
                        boardEl.insertBefore(newListEl, addSec);
                        if (input) input.value = '';
                    }
                } else {
                    alert((res.json && res.json.error) ? res.json.error : 'Falha ao criar lista.');
                }
            });
        });
    }

    document.addEventListener('click', function (e) {
        var t = e.target;
        if (!t) return;

        var act = t.getAttribute && t.getAttribute('data-action');

        if (act === 'toggle-done') {
            e.preventDefault();
            e.stopPropagation();
            var cardRoot2 = t && t.closest ? t.closest('.kb-card[data-card-id]') : null;
            if (!cardRoot2) return;
            var cardId2 = cardRoot2.getAttribute('data-card-id');
            if (!cardId2) return;
            postForm('/kanban/cartao/concluido/toggle', { card_id: String(cardId2) }).then(function (res) {
                if (res.json && res.json.ok) {
                    var done = (res.json && String(res.json.is_done) !== '0');
                    cardRoot2.setAttribute('data-is-done', done ? '1' : '0');
                    if (done) cardRoot2.classList.add('is-done');
                    else cardRoot2.classList.remove('is-done');
                    var btn = cardRoot2.querySelector('.kb-card-done-btn');
                    if (btn) btn.textContent = done ? '✓' : '○';
                } else {
                    alert((res.json && res.json.error) ? res.json.error : 'Falha ao atualizar cartão.');
                }
            });
            return;
        }

        if (act === 'delete-list') {
            var listId = t.getAttribute('data-list-id');
            if (!listId) return;
            if (!confirm('Excluir esta lista?')) return;
            postForm('/kanban/lista/excluir', { list_id: String(listId) }).then(function (res) {
                if (res.json && res.json.ok) {
                    var listEl = document.querySelector('.kb-list[data-list-id="' + String(listId) + '"]');
                    if (listEl && listEl.parentNode) listEl.parentNode.removeChild(listEl);
                } else {
                    alert((res.json && res.json.error) ? res.json.error : 'Falha ao excluir lista.');
                }
            });
        }

        if (act === 'add-card') {
            var listId2 = t.getAttribute('data-list-id');
            if (!listId2) return;
            openModal({
                title: 'Novo cartão',
                value: '',
                dueDate: '',
                showDesc: true,
                showDelete: false,
                mode: 'new-card',
                onSave: function (title, desc) {
                    var dd = $('kb-modal-due-date');
                    var due = dd ? (dd.value || '') : '';
                    postForm('/kanban/cartao/criar', { list_id: String(listId2), title: title || '', description: desc || '', due_date: due || '' }).then(function (res) {
                        if (res.json && res.json.ok && res.json.card) {
                            var container = getCardsContainer(listId2);
                            if (container) {
                                container.appendChild(createCardEl(res.json.card, listId2));
                            }
                            closeModal();
                        } else {
                            alert((res.json && res.json.error) ? res.json.error : 'Falha ao criar cartão.');
                        }
                    });
                }
            });
        }

        var cardRoot = t && t.closest ? t.closest('.kb-card[data-card-id]') : null;
        if (cardRoot) {
            var cardId = cardRoot.getAttribute('data-card-id');
            var listId3 = cardRoot.getAttribute('data-list-id');
            var titleEl = cardRoot.querySelector('.kb-card-title');
            var descEl = cardRoot.querySelector('.kb-card-desc');
            var title = titleEl ? (titleEl.textContent || '').trim() : '';
            var desc = descEl ? (descEl.textContent || '') : '';
            var dueAttr = cardRoot.getAttribute('data-due-date') || '';
            var coverUrlAttr = cardRoot.getAttribute('data-cover-url') || '';
            openModal({
                title: 'Editar cartão',
                value: title,
                desc: desc,
                dueDate: dueAttr,
                showDesc: true,
                showDelete: true,
                mode: 'edit-card',
                cardId: cardId,
                listId: listId3,
                coverUrl: coverUrlAttr,
                onDelete: function () {
                    if (!confirm('Excluir este cartão?')) return;
                    postForm('/kanban/cartao/excluir', { card_id: String(cardId) }).then(function (res) {
                        if (res.json && res.json.ok) {
                            var el = document.querySelector('.kb-card[data-card-id="' + String(cardId) + '"]');
                            if (el && el.parentNode) el.parentNode.removeChild(el);
                            closeModal();
                        } else {
                            alert((res.json && res.json.error) ? res.json.error : 'Falha ao excluir cartão.');
                        }
                    });
                },
                onSave: function (tNew, dNew) {
                    var ddIn = $('kb-modal-due-date');
                    var dueNew = ddIn ? (ddIn.value || '') : '';
                    postForm('/kanban/cartao/atualizar', { card_id: String(cardId), title: tNew || '', description: dNew || '', due_date: dueNew || '' }).then(function (res) {
                        if (res.json && res.json.ok) {
                            var el = document.querySelector('.kb-card[data-card-id="' + String(cardId) + '"]');
                            if (el) {
                                var tt = el.querySelector('.kb-card-title');
                                if (tt) tt.textContent = tNew || 'Sem título';

                                var dueTrim = (dueNew || '').trim();
                                el.setAttribute('data-due-date', dueTrim);

                                var badgesWrap = el.querySelector('.kb-card-badges');
                                if (!badgesWrap) {
                                    badgesWrap = document.createElement('div');
                                    badgesWrap.className = 'kb-card-badges';
                                    var descExisting = el.querySelector('.kb-card-desc');
                                    if (descExisting) {
                                        el.insertBefore(badgesWrap, descExisting);
                                    } else {
                                        el.appendChild(badgesWrap);
                                    }
                                }

                                var dueBadge = badgesWrap.querySelector('[data-badge="due"]');
                                if (dueTrim) {
                                    var info = getDueBadgeInfo(dueTrim);
                                    if (!dueBadge) {
                                        dueBadge = document.createElement('span');
                                        dueBadge.setAttribute('data-badge', 'due');
                                        badgesWrap.insertBefore(dueBadge, badgesWrap.firstChild);
                                    }
                                    dueBadge.className = 'kb-badge ' + (info ? info.cls : 'kb-badge--due-ok');
                                    dueBadge.setAttribute('data-due-raw', dueTrim);
                                    dueBadge.innerHTML = '<span style="opacity:0.9;">⏰</span><span>' + esc(info ? info.label : dueTrim) + '</span>';
                                } else {
                                    if (dueBadge && dueBadge.parentNode) {
                                        dueBadge.parentNode.removeChild(dueBadge);
                                    }
                                }

                                var dd = el.querySelector('.kb-card-desc');
                                var dTrim = (dNew || '').trim();
                                if (dTrim) {
                                    if (!dd) {
                                        dd = document.createElement('div');
                                        dd.className = 'kb-card-desc';
                                        el.appendChild(dd);
                                    }
                                    dd.textContent = dTrim;
                                } else if (dd && dd.parentNode) {
                                    dd.parentNode.removeChild(dd);
                                }

                                // Se não houver nenhum badge (due, anexos, checklist), esconde container
                                if (badgesWrap) {
                                    var hasAny = badgesWrap.querySelector('[data-badge]');
                                    if (!hasAny) {
                                        if (badgesWrap.parentNode) badgesWrap.parentNode.removeChild(badgesWrap);
                                    }
                                }
                            }
                            closeModal();
                        } else {
                            alert((res.json && res.json.error) ? res.json.error : 'Falha ao salvar.');
                        }
                    });
                }
            });

            var att = $('kb-attachments');
            if (att) {
                att.style.display = 'block';
            }
            loadAttachments(cardId);

            var chk = $('kb-checklist');
            if (chk) {
                chk.style.display = 'block';
            }
            loadChecklist(cardId);

            updateModalCoverVisual(coverUrlAttr);

            var coverClear = $('kb-cover-clear');
            if (coverClear) {
                coverClear.style.display = coverUrlAttr ? 'inline-flex' : 'none';
                coverClear.onclick = function () {
                    postForm('/kanban/cartao/capa/remover', { card_id: String(cardId) }).then(function (res) {
                        if (res.json && res.json.ok) {
                            var cardEl2 = getCardEl(cardId);
                            if (cardEl2) {
                                cardEl2.setAttribute('data-cover-url', '');
                                var coverBox = cardEl2.querySelector('.kb-card-cover');
                                if (coverBox && coverBox.parentNode) coverBox.parentNode.removeChild(coverBox);
                            }
                            var modalEl = $('kb-modal');
                            if (modalEl) modalEl.dataset.coverUrl = '';
                            var preview = $('kb-cover-preview');
                            if (preview) preview.innerHTML = '<div class="kb-cover-empty">Sem capa.</div>';
                            var file = $('kb-cover-file');
                            if (file) file.value = '';
                            coverClear.style.display = 'none';
                        } else {
                            alert((res.json && res.json.error) ? res.json.error : 'Falha ao remover capa.');
                        }
                    });
                };
            }

            var moveRow = $('kb-move-row');
            var moveSel = $('kb-move-list');
            if (moveRow && moveSel) {
                moveRow.style.display = 'block';
                buildListOptions(moveSel, listId3);
                moveSel.onchange = function () {
                    var modal = $('kb-modal');
                    if (!modal) return;
                    var currentListId = modal.dataset.listId;
                    var toListId = this.value;
                    if (!toListId || String(toListId) === String(currentListId)) {
                        return;
                    }
                    moveCardToList(cardId, currentListId, toListId);
                    modal.dataset.listId = String(toListId);
                };
            }
        }

        if (t.classList && t.classList.contains('kb-list-title')) {
            var listId4 = t.getAttribute('data-list-id');
            var currentTitle = t.textContent || '';
            openModal({
                title: 'Renomear lista',
                value: currentTitle,
                showDesc: false,
                showDelete: false,
                onSave: function (newTitle) {
                    postForm('/kanban/lista/renomear', { list_id: String(listId4), title: newTitle || '' }).then(function (res) {
                        if (res.json && res.json.ok) {
                            t.textContent = newTitle || 'Sem título';
                            closeModal();
                        } else {
                            alert((res.json && res.json.error) ? res.json.error : 'Falha ao renomear.');
                        }
                    });
                }
            });
        }
    });

    function serializeListOrder() {
        var board = $('kb-board');
        if (!board) return [];
        var lists = board.querySelectorAll('.kb-list[data-list-id]');
        var out = [];
        for (var i = 0; i < lists.length; i++) {
            out.push(parseInt(lists[i].getAttribute('data-list-id') || '0', 10));
        }
        return out.filter(function (n) { return n > 0; });
    }

    function serializeCardOrder(listId) {
        var container = document.querySelector('.kb-cards[data-cards-list-id="' + String(listId) + '"]');
        if (!container) return [];
        var cards = container.querySelectorAll('.kb-card[data-card-id]');
        var out = [];
        for (var i = 0; i < cards.length; i++) {
            out.push(parseInt(cards[i].getAttribute('data-card-id') || '0', 10));
        }
        return out.filter(function (n) { return n > 0; });
    }

    var drag = { type: '', id: 0, fromListId: 0 };

    var dnd = {
        listPlaceholder: null,
        cardPlaceholder: null,
    };

    function cleanupPlaceholders() {
        if (dnd.listPlaceholder && dnd.listPlaceholder.parentNode) {
            dnd.listPlaceholder.parentNode.removeChild(dnd.listPlaceholder);
        }
        if (dnd.cardPlaceholder && dnd.cardPlaceholder.parentNode) {
            dnd.cardPlaceholder.parentNode.removeChild(dnd.cardPlaceholder);
        }
        dnd.listPlaceholder = null;
        dnd.cardPlaceholder = null;
    }

    document.addEventListener('dragstart', function (e) {
        var el = e.target;
        if (!el) return;

        cleanupPlaceholders();

        if (el.classList && el.classList.contains('kb-card')) {
            drag.type = 'card';
            drag.id = parseInt(el.getAttribute('data-card-id') || '0', 10);
            drag.fromListId = parseInt(el.getAttribute('data-list-id') || '0', 10);
            el.classList.add('kb-card--dragging');
            dnd.cardPlaceholder = createCardPlaceholder(el);
            try { e.dataTransfer.setData('text/plain', 'card:' + String(drag.id)); } catch (err) {}
        }

        if (el.classList && el.classList.contains('kb-list') && el.getAttribute('data-list-id')) {
            drag.type = 'list';
            drag.id = parseInt(el.getAttribute('data-list-id') || '0', 10);
            drag.fromListId = 0;
            el.classList.add('kb-list--dragging');
            dnd.listPlaceholder = createListPlaceholder();
            try { e.dataTransfer.setData('text/plain', 'list:' + String(drag.id)); } catch (err) {}
        }
    });

    document.addEventListener('dragend', function () {
        if (drag.type === 'card') {
            var card = getCardEl(drag.id);
            if (card) card.classList.remove('kb-card--dragging');
        }
        if (drag.type === 'list') {
            var list = getListEl(drag.id);
            if (list) list.classList.remove('kb-list--dragging');
        }

        cleanupPlaceholders();

        drag.type = '';
        drag.id = 0;
        drag.fromListId = 0;
    });

    document.addEventListener('dragover', function (e) {
        if (!drag.type) return;
        e.preventDefault();

        if (drag.type === 'list') {
            var boardEl = getBoardEl();
            if (!boardEl || !dnd.listPlaceholder) return;

            var overList = e.target && e.target.closest ? e.target.closest('.kb-list[data-list-id]') : null;
            if (!overList) return;

            // não permitir placeholder antes do bloco "Adicionar lista"
            if (overList && overList.id === 'kb-add-list-section') return;

            var rect = overList.getBoundingClientRect();
            var before = (e.clientX - rect.left) < rect.width / 2;
            if (before) {
                if (dnd.listPlaceholder !== overList.previousSibling) {
                    boardEl.insertBefore(dnd.listPlaceholder, overList);
                }
            } else {
                if (overList.nextSibling) {
                    boardEl.insertBefore(dnd.listPlaceholder, overList.nextSibling);
                } else {
                    var addSec = getAddListSection();
                    if (addSec) {
                        boardEl.insertBefore(dnd.listPlaceholder, addSec);
                    } else {
                        boardEl.appendChild(dnd.listPlaceholder);
                    }
                }
            }
        }

        if (drag.type === 'card') {
            if (!dnd.cardPlaceholder) return;

            var listContainer = e.target && e.target.closest ? e.target.closest('.kb-cards[data-cards-list-id]') : null;
            if (!listContainer) {
                var overList2 = e.target && e.target.closest ? e.target.closest('.kb-list[data-list-id]') : null;
                if (overList2) {
                    listContainer = overList2.querySelector('.kb-cards[data-cards-list-id]');
                }
            }
            if (!listContainer) return;

            var overCard = e.target && e.target.closest ? e.target.closest('.kb-card[data-card-id]') : null;
            if (overCard && overCard.classList.contains('kb-card--dragging')) {
                overCard = null;
            }

            ensureCardPlaceholderHeight(dnd.cardPlaceholder, overCard);

            if (overCard) {
                var rect2 = overCard.getBoundingClientRect();
                var before2 = (e.clientY - rect2.top) < rect2.height / 2;
                if (before2) listContainer.insertBefore(dnd.cardPlaceholder, overCard);
                else listContainer.insertBefore(dnd.cardPlaceholder, overCard.nextSibling);
            } else {
                listContainer.appendChild(dnd.cardPlaceholder);
            }
        }
    });

    document.addEventListener('drop', function (e) {
        if (!drag.type) return;
        e.preventDefault();

        if (drag.type === 'list') {
            var boardEl = $('kb-board');
            if (!boardEl) { drag.type=''; return; }
            var moving = boardEl.querySelector('.kb-list[data-list-id="' + String(drag.id) + '"]');
            if (!moving) { drag.type=''; return; }

            if (dnd.listPlaceholder && dnd.listPlaceholder.parentNode) {
                boardEl.insertBefore(moving, dnd.listPlaceholder);
                dnd.listPlaceholder.parentNode.removeChild(dnd.listPlaceholder);
                dnd.listPlaceholder = null;

                var order = serializeListOrder();
                postSync({ board_id: boardId, list_order: order });
            }
        }

        if (drag.type === 'card') {
            var listContainer = e.target && e.target.closest ? e.target.closest('.kb-cards[data-cards-list-id]') : null;
            if (!listContainer) {
                var overList3 = e.target && e.target.closest ? e.target.closest('.kb-list[data-list-id]') : null;
                if (overList3) {
                    listContainer = overList3.querySelector('.kb-cards[data-cards-list-id]');
                }
            }
            if (!listContainer) { drag.type=''; return; }

            var toListId = parseInt(listContainer.getAttribute('data-cards-list-id') || '0', 10);
            if (!toListId) { drag.type=''; return; }

            var movingCard = document.querySelector('.kb-card[data-card-id="' + String(drag.id) + '"]');
            if (!movingCard) { drag.type=''; return; }

            if (dnd.cardPlaceholder && dnd.cardPlaceholder.parentNode) {
                listContainer.insertBefore(movingCard, dnd.cardPlaceholder);
                dnd.cardPlaceholder.parentNode.removeChild(dnd.cardPlaceholder);
                dnd.cardPlaceholder = null;
            } else {
                listContainer.appendChild(movingCard);
            }

            movingCard.setAttribute('data-list-id', String(toListId));

            var newOrder = serializeCardOrder(toListId);
            var newPos = 1;
            for (var i = 0; i < newOrder.length; i++) {
                if (newOrder[i] === drag.id) { newPos = i + 1; break; }
            }

            var payload = { board_id: boardId, cards_by_list: {} };
            payload.cards_by_list[String(toListId)] = newOrder;
            if (drag.fromListId && drag.fromListId !== toListId) {
                var oldOrder = serializeCardOrder(drag.fromListId);
                payload.cards_by_list[String(drag.fromListId)] = oldOrder;
            }
            postSync(payload);
        }

        // dragend vai limpar estado e placeholders
    });
})();
</script>
