@extends('layouts.app', ['title' => __tr('Créer un modèle WhatsApp (Wizard)')])

@section('content')
<div class="header pb-5 pt-5 pt-lg-6 d-flex align-items-center">
    <div class="container-fluid d-flex align-items-center">
        <div class="col-lg-7 pl-0">
            <div class="mb-4">
                <h1 class="lw-page-title display-2 mt-md-4">{{ __tr('Créateur de modèle Meta (Assistant)') }}</h1>
                <p class="mt-0 mb-3 text-muted">
                    {{ __tr('Créez vos modèles de message WhatsApp étape par étape avec un aperçu en direct.') }}
                    <span class="badge badge-pill badge-primary ml-2" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none;"><i class="fas fa-magic mr-1"></i>Assistant Pro</span>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    /* Premium style for Wizard steps */
    .lw-wizard-steps {
        margin-bottom: 25px;
    }
    .lw-wizard-steps .step-item {
        flex: 1;
        position: relative;
    }
    .lw-wizard-steps .step-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background-color: #fff;
        border: 2px solid #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin: 0 auto 8px;
        transition: all 0.3s ease;
        position: relative;
        z-index: 2;
    }
    .lw-wizard-steps .step-item.active .step-circle {
        border-color: #5e72e4;
        background-color: #5e72e4;
        color: #fff;
        box-shadow: 0 0 0 3px rgba(94, 114, 228, 0.25);
    }
    .lw-wizard-steps .step-item.completed .step-circle {
        border-color: #2dce89;
        background-color: #2dce89;
        color: #fff;
    }
    .lw-wizard-steps .step-item::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 2px;
        background-color: #e2e8f0;
        top: 19px;
        left: 50%;
        z-index: 1;
        transition: background-color 0.3s ease;
    }
    .lw-wizard-steps .step-item:last-child::after {
        display: none;
    }
    .lw-wizard-steps .step-item.completed::after {
        background-color: #2dce89;
    }

    /* WhatsApp Smartphone Preview */
    .lw-phone-preview-wrapper {
        position: -webkit-sticky;
        position: sticky;
        top: 90px;
        z-index: 10;
        margin-bottom: 20px;
    }
    .lw-phone-container {
        width: 320px;
        background-color: #efeae2;
        background-image: url("{{ asset('imgs/wa-message-bg.png') }}");
        background-repeat: repeat;
        border: 12px solid #1e293b;
        border-radius: 32px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        margin: 0 auto;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    .lw-phone-header {
        background-color: #075e54;
        color: #fff;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .lw-phone-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0f766e, #0d9488);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: bold;
        font-size: 13px;
        margin-right: 10px;
    }
    .lw-phone-username {
        font-weight: 600;
        font-size: 13.5px;
    }
    .lw-phone-chat-area {
        height: 480px;
        padding: 12px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }
    .lw-wa-bubble {
        background-color: #fff;
        border-radius: 7.5px;
        padding: 8px 10px;
        box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
        max-width: 90%;
        align-self: flex-start;
        position: relative;
        margin-bottom: 5px;
    }
    .lw-wa-bubble::before {
        content: '';
        position: absolute;
        top: 0;
        left: -8px;
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 0 8px 8px 0;
        border-color: transparent #fff transparent transparent;
    }
    .lw-wa-header-media {
        background-color: #cbd5e1;
        border-radius: 6px;
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #475569;
        margin-bottom: 6px;
        font-size: 28px;
        overflow: hidden;
    }
    .lw-wa-header-text {
        font-weight: 700;
        color: #111827;
        font-size: 13px;
        margin-bottom: 4px;
        line-height: 1.3;
    }
    .lw-wa-body-text {
        font-size: 13px;
        color: #334155;
        line-height: 1.4;
        white-space: pre-wrap;
    }
    .lw-wa-body-text strong {
        font-weight: 700;
    }
    .lw-wa-footer-text {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 4px;
    }
    .lw-wa-buttons-area {
        border-top: 1px solid #f1f5f9;
        margin-top: 8px;
        padding-top: 4px;
    }
    .lw-wa-btn {
        color: #06b6d4;
        font-weight: 500;
        font-size: 12.5px;
        text-align: center;
        padding: 6px 0;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .lw-wa-btn:last-child {
        border-bottom: none;
    }
    .lw-wa-btn i {
        font-size: 11px;
        margin-right: 6px;
    }

    /* Option Cards styling */
    .lw-option-card {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background-color: #fff;
    }
    .lw-option-card:hover {
        border-color: #94a3b8;
        transform: translateY(-2px);
    }
    .lw-option-card.selected {
        border-color: #5e72e4;
        background-color: #f8fafd;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .lw-option-card i {
        font-size: 24px;
        color: #64748b;
        margin-bottom: 8px;
    }
    .lw-option-card.selected i {
        color: #5e72e4;
    }
    .lw-option-card-title {
        font-weight: 600;
        font-size: 14px;
        color: #334155;
    }
</style>


<script>
    function templateWizard() {
        return {
            
            init() {
                this.$watch('headerType', value => {
                    if (value === 'location') {
                        this.category = 'UTILITY';
                    }
                });
                this.$watch('category', value => {
                    if (value === 'MARKETING' && this.headerType === 'location') {
                        alert("Meta n'autorise les entêtes de type 'Carte / Position' que pour les modèles de catégorie 'UTILITY'. L'entête a été réinitialisé.");
                        this.headerType = '0';
                    }
                    this.analyzeTemplateQuality();
                });
                this.$watch('text_body', () => this.analyzeTemplateQuality());
                this.$watch('customButtons', () => this.analyzeTemplateQuality(), { deep: true });
                setTimeout(() => this.analyzeTemplateQuality(), 500);
            },
            step: 1,

            changeTemplateType(type) {
                this.templateType = type;
                if (type === 'carousel' && this.step > 5) this.step = 5;
                if (type === 'header' && this.step > 6) this.step = 6;
                this.analyzeTemplateQuality();
            },

            quickTemplates: {
                MARKETING: [
                    { name: 'Promo Flash', text: '🔥 Promo Flash ! Profitez de @{{1}}% de réduction sur tout le site avec le code @{{2}}. Offre valable jusqu\'à ce soir !' },
                    { name: 'Bienvenue', text: '👋 Bonjour @{{1}}, bienvenue chez nous ! Nous sommes ravis de vous compter parmi nous. Découvrez nos offres exclusives ici.' }
                ],
                UTILITY: [
                    { name: 'Confirmation de Commande', text: '📦 Bonjour @{{1}}, votre commande numéro @{{2}} a bien été confirmée. Vous pouvez suivre son statut à tout moment.' },
                    { name: 'Rappel de RDV', text: '🗓️ Bonjour @{{1}}, ceci est un rappel pour votre rendez-vous prévu le @{{2}} à @{{3}}.' }
                ]
            },
            metaScore: 0,
            metaScoreMessage: 'Rédigez votre message',
            metaScoreColor: 'bg-secondary',
            
            applyQuickTemplate(category, index) {
                const template = this.quickTemplates[category][index];
                this.text_body = template.text;
                this.updateBodyVariables();
                setTimeout(() => this.analyzeTemplateQuality(), 100);
            },
            
            insertVariable(defaultName) {
                const textarea = document.getElementById('lwTemplateBody');
                if (!textarea) return;
                
                const matches = this.text_body.match(/\{\{\d+\}\}/g);
                let nextIdx = 1;
                if (matches) {
                    const nums = matches.map(m => parseInt(m.replace(/[^0-9]/g, '')));
                    nextIdx = Math.max(...nums) + 1;
                }
                
                const varString = '@{{' + nextIdx + '}}';
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = this.text_body;
                
                this.text_body = text.substring(0, start) + varString + text.substring(end);
                this.updateBodyVariables();
                setTimeout(() => this.analyzeTemplateQuality(), 100);
            },

            analyzeTemplateQuality() {
                let score = 90; // Start at 90 to indicate Meta is never 100% guaranteed
                let messages = [];
                
                if (this.templateType === 'header') {
                    if (!this.text_body || this.text_body.trim() === '') {
                        this.metaScore = 0;
                        this.metaScoreMessage = 'Rédigez votre message';
                        this.metaScoreColor = 'bg-secondary';
                        return;
                    }

                    if (this.text_body.length < 10) {
                        score -= 20;
                        messages.push("Texte un peu court.");
                    }

                    if ((this.text_body.match(/!{2,}/g) || []).length > 0) {
                        score -= 30;
                        messages.push("Évitez les points d'exclamation multiples.");
                    }

                    if (this.text_body.match(/\{\{\d+\}\}\s*\{\{\d+\}\}/g)) {
                        score -= 40;
                        messages.push("Interdit d'enchaîner deux variables sans texte.");
                    }
                    
                    if (this.category === 'UTILITY') {
                        const promoWords = ['promo', 'offre', 'gratuit', 'réduction', '%', 'soldes'];
                        const lowerText = this.text_body.toLowerCase();
                        if (promoWords.some(w => lowerText.includes(w))) {
                            score -= 50;
                            messages.push("Attention : Mots promotionnels risqués en Utilitaire.");
                        }
                    }
                    
                    if (this.customButtons.length === 0) {
                        score -= 10;
                        messages.push("Astuce: Un bouton augmente l'interaction.");
                    }
                } else {
                    if (!this.carousel_body_text || this.carousel_body_text.trim() === '') {
                        this.metaScore = 0;
                        this.metaScoreMessage = 'Rédigez le message principal';
                        this.metaScoreColor = 'bg-secondary';
                        return;
                    }
                    
                    if (this.carouselTemplateContainer.length === 0) {
                        score -= 40;
                        messages.push("Ajoutez au moins une carte au carrousel.");
                    } else if (this.carouselTemplateContainer.length < 2) {
                        score -= 10;
                        messages.push("Un carrousel avec une seule carte est peu utile.");
                    }

                    if (this.category === 'UTILITY') {
                        const promoWords = ['promo', 'offre', 'gratuit', 'réduction', '%', 'soldes'];
                        const lowerText = this.carousel_body_text.toLowerCase();
                        if (promoWords.some(w => lowerText.includes(w))) {
                            score -= 50;
                            messages.push("Attention : Mots promotionnels risqués en Utilitaire.");
                        }
                    }

                    let hasButtons = this.carouselTemplateContainer.some(c => c.buttons && c.buttons.length > 0);
                    if (!hasButtons) {
                        score -= 10;
                        messages.push("Astuce: Ajoutez des boutons à vos cartes pour plus d'interaction.");
                    }
                }

                this.metaScore = Math.max(0, score);
                
                if (this.metaScore >= 80) {
                    this.metaScoreMessage = messages[0] || "Probabilité d'approbation élevée";
                    this.metaScoreColor = "bg-success";
                } else if (this.metaScore >= 60) {
                    this.metaScoreMessage = messages[0] || "Modèle améliorable";
                    this.metaScoreColor = "bg-warning";
                } else {
                    this.metaScoreMessage = messages[0] || "Risque de rejet par Meta";
                    this.metaScoreColor = "bg-danger";
                }
            },


            templateType: 'header',
            
            // Carousel specific
            carousel_body_text: '',
            newCarouselBodyTextInputFields: {},
            carouselBodyVariablesData: {},
            
            carouselTemplateContainer: [
                {
                    id: 'card_' + Date.now() + '_1',
                    headerType: 'image',
                    bodyText: '',
                    variablesInputs: {},
                    variablesData: {},
                    buttons: []
                }
            ],
            
            templateName: '',
            languageCode: 'fr',
            category: 'MARKETING',
            headerType: '0', 
            header_text_body: '',
            enableHeaderVariableExample: false,
            headerVariableExample: '',
            
            text_body: '',
            newBodyTextInputFields: {}, 
            bodyVariablesData: {}, 
            
            footer_text_body: '',
            
            customButtons: [],
            
            
            goToStep(s) {
                // Moving forward validation
                if (s > this.step) {
                    if (this.step === 1) {
                        if (!this.templateName) {
                            alert('Veuillez saisir le nom du modèle.');
                            return;
                        }
                        if (!/^[a-z0-9_]+$/.test(this.templateName)) {
                            alert('Le nom du modèle ne peut contenir que des lettres minuscules, des chiffres et des tirets bas (_).');
                            return;
                        }
                        if (!this.languageCode) {
                            alert('Veuillez sélectionner une langue.');
                            return;
                        }
                    }
                    if (this.templateType === 'header') {
                        if (this.step === 3) {
                            if (!this.text_body) {
                                alert('Le corps du message est obligatoire.');
                                return;
                            }
                            let hasEmptyExample = false;
                            Object.keys(this.newBodyTextInputFields).forEach(k => {
                                if (!this.bodyVariablesData[k]) {
                                    hasEmptyExample = true;
                                }
                            });
                            if (hasEmptyExample) {
                                alert('Veuillez renseigner un exemple pour chaque variable du corps de message.');
                                return;
                            }
                        }
                        if (this.step === 2 && this.headerType === 'text' && this.enableHeaderVariableExample && !this.headerVariableExample) {
                            alert('Veuillez renseigner un exemple pour la variable d\'en-tête.');
                            return;
                        }
                        if (this.step === 5) {
                            let incompleteButton = this.customButtons.some(b => {
                                if (!b.text) return true;
                                if (b.type === 'PHONE_NUMBER' && !b.phone_number) return true;
                                if ((b.type === 'URL_BUTTON' || b.type === 'DYNAMIC_URL_BUTTON') && !b.url) return true;
                                if (b.type === 'DYNAMIC_URL_BUTTON' && !b.example) return true;
                                if (b.type === 'COPY_CODE' && !b.example) return true;
                                return false;
                            });
                            if (incompleteButton) {
                                alert('Veuillez configurer entièrement tous les boutons créés ou supprimer les boutons inutilisés.');
                                return;
                            }
                        }
                    } else if (this.templateType === 'carousel') {
                        if (this.step === 2) {
                            // Check carousel body
                            let hasEmptyExample = false;
                            Object.keys(this.newCarouselBodyTextInputFields).forEach(k => {
                                if (!this.carouselBodyVariablesData[k]) {
                                    hasEmptyExample = true;
                                }
                            });
                            if (hasEmptyExample) {
                                alert('Veuillez renseigner un exemple pour chaque variable du corps principal.');
                                return;
                            }
                        }
                        if (this.step === 3) {
                            // Check cards
                            if (this.carouselTemplateContainer.length === 0) {
                                alert('Veuillez ajouter au moins une carte.');
                                return;
                            }
                            let invalidCard = this.carouselTemplateContainer.some((card, idx) => {
                                if (!card.bodyText) return true;
                                let emptyVar = false;
                                Object.keys(card.variablesInputs).forEach(k => {
                                    if (!card.variablesData[k]) emptyVar = true;
                                });
                                return emptyVar;
                            });
                            if (invalidCard) {
                                alert('Veuillez remplir le texte et toutes les variables pour chaque carte.');
                                return;
                            }
                        }
                        if (this.step === 4) {
                            if (this.carouselCustomButtons.length === 0) {
                                alert('Meta exige au moins 1 bouton pour les modèles Carrousel.');
                                return;
                            }
                            let incompleteButton = this.carouselCustomButtons.some(b => {
                                if (!b.text) return true;
                                if (b.type === 'PHONE_NUMBER' && !b.phone_number) return true;
                                if ((b.type === 'URL_BUTTON' || b.type === 'DYNAMIC_URL_BUTTON') && !b.url) return true;
                                if (b.type === 'DYNAMIC_URL_BUTTON' && !b.example) return true;
                                return false;
                            });
                            if (incompleteButton) {
                                alert('Veuillez configurer entièrement tous les boutons.');
                                return;
                            }
                        }
                    }
                }
                this.step = s;
                
                this.$nextTick(() => {
                    window.lwPluginsInit();
                });
            },
sanitizeInput() {
                this.templateName = this.templateName.toLowerCase().replace(/[^a-z0-9_]/g, '');
            },
            
            isValidKey(e) {
                const key = e.key;
                const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'];
                const isLetter = key >= 'a' && key <= 'z';
                const isNumber = key >= '0' && key <= '9';
                const isUnderscore = key === '_';
                if (!isLetter && !isNumber && !isUnderscore && !allowedKeys.includes(key)) {
                    e.preventDefault();
                }
            },
            
            wrapBodyText(symbol) {
                const textarea = document.getElementById('lwTemplateBody');
                if (!textarea) return;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value;
                const selected = text.substring(start, end);
                const replacement = symbol + selected + symbol;
                
                this.text_body = text.substring(0, start) + replacement + text.substring(end);
                this.updateBodyVariables();
                
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.selectionStart = start + symbol.length;
                    textarea.selectionEnd = start + symbol.length + selected.length;
                });
            },
            
            addBodyPlaceholder() {
                const textarea = document.getElementById('lwTemplateBody');
                if (!textarea) return;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value;
                
                const regex = /\{\{(\d+)\}\}/g;
                let match;
                let maxNum = 0;
                while ((match = regex.exec(text)) !== null) {
                    let num = parseInt(match[1]);
                    if (num > maxNum) maxNum = num;
                }
                const nextVar = maxNum + 1;
                const placeholder = '{' + '{' + nextVar + '}' + '}';
                
                this.text_body = text.substring(0, start) + placeholder + text.substring(end);
                this.updateBodyVariables();
                
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
                });
            },

            updateBodyVariables() {
                const regex = /\{\{(\d+)\}\}/g;
                let match;
                let variables = [];
                while ((match = regex.exec(this.text_body)) !== null) {
                    let num = parseInt(match[1]);
                    if (!variables.includes(num)) {
                        variables.push(num);
                    }
                }
                variables.sort((a, b) => a - b);
                
                let newFields = {};
                variables.forEach((num, index) => {
                    newFields[index] = {
                        num: num,
                        varName: '{' + '{' + num + '}' + '}',
                        value: this.bodyVariablesData[index] || ''
                    };
                });
                this.newBodyTextInputFields = newFields;
                this.analyzeTemplateQuality();
            },
            
            addHeaderPlaceholder() {
                const textarea = document.getElementById('lwHeaderTextBody');
                if (!textarea) return;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value;
                const placeholder = ' {' + '{1}' + '} ';
                
                this.header_text_body = text.substring(0, start) + placeholder + text.substring(end);
                this.enableHeaderVariableExample = true;
                
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
                });
            },
            
            addButton(type) {
                if (this.customButtons.length >= 10) return;
                
                if (type === 'PHONE_NUMBER' && this.hasButtonType('PHONE_NUMBER')) {
                    alert('Meta autorise un seul bouton de numéro de téléphone.');
                    return;
                }
                if (type === 'COPY_CODE' && this.hasButtonType('COPY_CODE')) {
                    alert('Meta autorise un seul bouton de copie de code.');
                    return;
                }
                if ((type === 'URL_BUTTON' || type === 'DYNAMIC_URL_BUTTON') && this.getUrlButtonCount() >= 2) {
                    alert('Meta autorise un maximum de 2 boutons URL.');
                    return;
                }
                
                this.customButtons.push({
                    id: 'btn_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                    type: type,
                    text: '',
                    phone_number: '',
                    url: '',
                    example: ''
                });
                this.analyzeTemplateQuality();
            },
            
            deleteButton(index) {
                this.customButtons.splice(index, 1);
                this.analyzeTemplateQuality();
            },
            
            hasButtonType(type) {
                return this.customButtons.some(b => b.type === type);
            },
            
            getUrlButtonCount() {
                return this.customButtons.filter(b => b.type === 'URL_BUTTON' || b.type === 'DYNAMIC_URL_BUTTON').length;
            },
            
            getButtonTypeText(type) {
                switch(type) {
                    case 'QUICK_REPLY': return 'Réponse rapide';
                    case 'PHONE_NUMBER': return 'Appel téléphonique';
                    case 'URL_BUTTON': return 'Lien site web';
                    case 'DYNAMIC_URL_BUTTON': return 'Lien site dynamique';
                    case 'COPY_CODE': return 'Copier le code promo';
                    default: return type;
                }
            },

            updateCarouselBodyVariables() {
                const regex = /\{\{(\d+)\}\}/g;
                let match;
                let variables = [];
                while ((match = regex.exec(this.carousel_body_text)) !== null) {
                    let num = parseInt(match[1]);
                    if (!variables.includes(num)) variables.push(num);
                }
                variables.sort((a, b) => a - b);
                let newFields = {};
                variables.forEach((num, index) => {
                    newFields[index] = {
                        num: num,
                        varName: '{' + '{' + num + '}' + '}',
                        value: this.carouselBodyVariablesData[index] || ''
                    };
                });
                this.newCarouselBodyTextInputFields = newFields;
                this.analyzeTemplateQuality();
            },
            
            wrapCarouselBodyText(symbol) {
                const textarea = document.getElementById('lwCarouselTemplateBody');
                if (!textarea) return;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value;
                const selected = text.substring(start, end);
                const replacement = symbol + selected + symbol;
                this.carousel_body_text = text.substring(0, start) + replacement + text.substring(end);
                this.updateCarouselBodyVariables();
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.selectionStart = start + symbol.length;
                    textarea.selectionEnd = start + symbol.length + selected.length;
                });
            },
            
            addCarouselBodyPlaceholder() {
                const textarea = document.getElementById('lwCarouselTemplateBody');
                if (!textarea) return;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value;
                const regex = /\{\{(\d+)\}\}/g;
                let match;
                let maxNum = 0;
                while ((match = regex.exec(text)) !== null) {
                    let num = parseInt(match[1]);
                    if (num > maxNum) maxNum = num;
                }
                const nextVar = maxNum + 1;
                const placeholder = '{' + '{' + nextVar + '}' + '}';
                this.carousel_body_text = text.substring(0, start) + placeholder + text.substring(end);
                this.updateCarouselBodyVariables();
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
                });
            },
            
            addNewCard() {
                if (this.carouselTemplateContainer.length >= 10) return;
                const newId = 'card_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                this.carouselTemplateContainer.push({
                    id: newId,
                    headerType: 'image',
                    bodyText: '',
                    variablesData: {},
                    variablesInputs: {},
                    buttons: []
                });
                this.analyzeTemplateQuality();
                this.$nextTick(() => {
                    if (window.lwPluginsInit) {
                        window.lwPluginsInit('#' + newId + ' ');
                    }
                });
            },
            
            deleteCard(index) {
                this.carouselTemplateContainer.splice(index, 1);
                this.analyzeTemplateQuality();
            },
            
            updateCardVariables(index) {
                const card = this.carouselTemplateContainer[index];
                if (!card) return;
                const regex = /\{\{(\d+)\}\}/g;
                let match;
                let variables = [];
                while ((match = regex.exec(card.bodyText)) !== null) {
                    let num = parseInt(match[1]);
                    if (!variables.includes(num)) variables.push(num);
                }
                variables.sort((a, b) => a - b);
                let newFields = {};
                variables.forEach((num, vIndex) => {
                    newFields[vIndex] = {
                        num: num,
                        varName: '{' + '{' + num + '}' + '}',
                        value: card.variablesData[vIndex] || ''
                    };
                });
                card.variablesInputs = newFields;
                this.analyzeTemplateQuality();
            },
            
            addCardButton(cardIndex, type) {
                let card = this.carouselTemplateContainer[cardIndex];
                if (!card.buttons) card.buttons = [];
                if (card.buttons.length >= 2) return;
                
                card.buttons.push({
                    id: 'btn_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                    type: type,
                    text: '',
                    phone_number: '',
                    url: '',
                    example: ''
                });
                this.analyzeTemplateQuality();
            },
            
            deleteCardButton(cardIndex, buttonIndex) {
                this.carouselTemplateContainer[cardIndex].buttons.splice(buttonIndex, 1);
                this.analyzeTemplateQuality();
            },
            
            changeCardButtonType(cardIndex, buttonIndex, newType) {
                let btn = this.carouselTemplateContainer[cardIndex].buttons[buttonIndex];
                btn.type = newType;
                btn.text = '';
                btn.phone_number = '';
                btn.url = '';
                this.analyzeTemplateQuality();
            },
            
            formatCarouselBodyPreview(text) {
                if (!text) return '';
                let formatted = text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
                    
                formatted = formatted.replace(/\*([^\*]+)\*/g, '<strong>$1</strong>');
                formatted = formatted.replace(/_([^_]+)_/g, '<em>$1</em>');
                formatted = formatted.replace(/~([^~]+)~/g, '<del>$1</del>');
                formatted = formatted.replace(/```([^`]+)```/g, '<code>$1</code>');
                
                const keys = Object.keys(this.newCarouselBodyTextInputFields);
                keys.forEach(k => {
                    const item = this.newCarouselBodyTextInputFields[k];
                    const replacement = item.value ? `<span class="badge badge-success px-1">${item.value}</span>` : `<span class="badge badge-warning px-1">${item.varName}</span>`;
                    formatted = formatted.replace(new RegExp('\\{\\{' + item.num + '\\}\\}', 'g'), replacement);
                });
                
                return formatted.replace(/\n/g, '<br>');
            },

            formatCardBodyPreview(index) {
                const card = this.carouselTemplateContainer[index];
                if (!card || !card.bodyText) return '';
                let text = card.bodyText;
                let formatted = text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
                    
                formatted = formatted.replace(/\*([^\*]+)\*/g, '<strong>$1</strong>');
                formatted = formatted.replace(/_([^_]+)_/g, '<em>$1</em>');
                formatted = formatted.replace(/~([^~]+)~/g, '<del>$1</del>');
                formatted = formatted.replace(/```([^`]+)```/g, '<code>$1</code>');
                
                const keys = Object.keys(card.variablesInputs || {});
                keys.forEach(k => {
                    const item = card.variablesInputs[k];
                    const val = card.variablesData[k];
                    const replacement = val ? `<span class="badge badge-success px-1">${val}</span>` : `<span class="badge badge-warning px-1">${item.varName}</span>`;
                    formatted = formatted.replace(new RegExp('\\{\\{' + item.num + '\\}\\}', 'g'), replacement);
                });
                
                return formatted.replace(/\n/g, '<br>');
            },

            formatWhatsAppHeaderPreview(text) {
                if (!text) return '';
                let formatted = text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
                    
                if (this.enableHeaderVariableExample || text.includes('{{1}}')) {
                    const replacement = this.headerVariableExample ? `<span class="badge badge-success px-1">${this.headerVariableExample}</span>` : `<span class="badge badge-warning px-1">{{1}}</span>`;
                    formatted = formatted.replace(/\{\{1\}\}/g, replacement);
                }
                
                return formatted;
            },

            formatWhatsAppPreview(text) {
                if (!text) return '';
                let formatted = text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
                    
                formatted = formatted.replace(/\*([^\*]+)\*/g, '<strong>$1</strong>');
                formatted = formatted.replace(/_([^_]+)_/g, '<em>$1</em>');
                formatted = formatted.replace(/~([^~]+)~/g, '<del>$1</del>');
                formatted = formatted.replace(/```([^`]+)```/g, '<code>$1</code>');
                
                const keys = Object.keys(this.newBodyTextInputFields);
                keys.forEach(k => {
                    const item = this.newBodyTextInputFields[k];
                    const replacement = item.value ? `<span class="badge badge-success px-1">${item.value}</span>` : `<span class="badge badge-warning px-1">${item.varName}</span>`;
                    formatted = formatted.replace(new RegExp('\\{' + '\\{' + item.num + '\\}' + '\\}', 'g'), replacement);
                });
                
                return formatted;
            }
        };
    }

    function onWizardFormSubmitSuccess(response) {
        // Automatically redirects to the templates list view on success
        if (response.reaction === 21 || response.success) {
            showSuccessMessage('Modèle créé avec succès ! En attente d\'approbation par Meta.');
        }
    }
</script>

<div class="container-fluid mt-lg--6" x-data="templateWizard()">
    <div class="row">
        <!-- Main Grid Content -->
        <!-- Form wizard column (left side) -->
        <div class="col-lg-8">
            
            <!-- Top Actions -->
            <div class="d-flex justify-content-end mb-3">
                <a class="btn btn-sm btn-secondary mr-2" href="{{ route('vendor.whatsapp_service.templates.read.list_view') }}">
                    <i class="fa fa-arrow-left mr-1"></i> <span class="d-none d-sm-inline">{{ __tr('Back to Templates') }}</span>
                </a>
                <a href="https://business.facebook.com/business/help/2055875911147364" target="_blank" class="btn btn-sm btn-default">
                    <i class="fa fa-question-circle mr-1"></i> <span class="d-none d-sm-inline">{{ __tr('Help') }}</span>
                </a>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body p-3">
                    <div class="lw-wizard-steps d-flex justify-content-between text-center">
                        <div class="step-item" :class="{ 'active': step == 1, 'completed': step > 1 }">
                            <div class="step-circle" @click="goToStep(1)">1</div>
                            <small class="d-none d-md-block" :class="step == 1 ? 'font-weight-bold text-primary' : 'text-muted'">{{ __tr('Infos de base') }}</small>
                        </div>
                        <div class="step-item" :class="{ 'active': step == 2, 'completed': step > 2 }">
                            <div class="step-circle" @click="goToStep(2)">2</div>
                            <small class="d-none d-md-block" :class="step == 2 ? 'font-weight-bold text-primary' : 'text-muted'" x-text="templateType == 'header' ? '{{ __tr('En-tête') }}' : '{{ __tr('Corps Général') }}'"></small>
                        </div>
                        <div class="step-item" :class="{ 'active': step == 3, 'completed': step > 3 }">
                            <div class="step-circle" @click="goToStep(3)">3</div>
                            <small class="d-none d-md-block" :class="step == 3 ? 'font-weight-bold text-primary' : 'text-muted'" x-text="templateType == 'header' ? '{{ __tr('Corps') }}' : '{{ __tr('Cartes') }}'"></small>
                        </div>
                        <div class="step-item" :class="{ 'active': step == 4, 'completed': step > 4 }">
                            <div class="step-circle" @click="goToStep(4)">4</div>
                            <small class="d-none d-md-block" :class="step == 4 ? 'font-weight-bold text-primary' : 'text-muted'" x-text="templateType == 'header' ? '{{ __tr('Pied de page') }}' : '{{ __tr('Revue') }}'"></small>
                        </div>
                        <div class="step-item" x-show="templateType == 'header'" :class="{ 'active': step == 5, 'completed': step > 5 }">
                            <div class="step-circle" @click="goToStep(5)">5</div>
                            <small class="d-none d-md-block" :class="step == 5 ? 'font-weight-bold text-primary' : 'text-muted'">{{ __tr('Boutons') }}</small>
                        </div>
                        <div class="step-item" x-show="templateType == 'header'" :class="{ 'active': step == 6 }">
                            <div class="step-circle" @click="goToStep(6)">6</div>
                            <small class="d-none d-md-block" :class="step == 6 ? 'font-weight-bold text-primary' : 'text-muted'">{{ __tr('Revue') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <x-lw.form id="lwNewTemplateWizardForm" :action="route('vendor.whatsapp_service.templates.write.create')" data-callback="onWizardFormSubmitSuccess">
                
                <!-- Hidden inputs for validation requirements of the backend -->
                <input id="lwMediaFileName" type="hidden" value="" name="uploaded_media_file_name" />

                <!-- Step 1: Basic Info -->
                <div x-show="step == 1">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom-0 pb-0">
                            <h3 class="font-weight-bold text-dark mb-0">{{ __tr('Étape 1: Informations de base') }}</h3>
                            <p class="text-muted text-sm mt-1">{{ __tr('Configurez le nom, la langue et la catégorie de votre modèle Meta.') }}</p>
                        </div>
                        <div class="card-body">

                            <!-- Template Category & Quick Templates -->
                            <div class="form-group mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="font-weight-600 text-dark mb-0">{{ __tr('Catégorie') }} <span class="text-danger">*</span></label>
                                    
                                    <!-- Modèles Rapides -->
                                    <div class="d-flex align-items-center" style="gap: 8px;">
                                        <span class="text-xs text-muted d-none d-md-inline">{{ __tr('Modèles rapides :') }}</span>
                                        <template x-for="(tpl, index) in quickTemplates[category]" :key="index">
                                            <button type="button" class="btn btn-xs btn-outline-primary shadow-sm" @click="applyQuickTemplate(category, index)" data-toggle="tooltip" title="{{ __tr('Remplir automatiquement avec ce modèle') }}">
                                                <i class="fas fa-magic mr-1"></i> <span x-text="tpl.name"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <div class="lw-option-card h-100" :class="{ 'selected': category == 'MARKETING' }" @click="category = 'MARKETING'; analyzeTemplateQuality()">
                                            <i class="fas fa-bullhorn text-warning"></i>
                                            <div class="lw-option-card-title">{{ __tr('Marketing') }}</div>
                                            <div class="text-xs text-muted mt-1">{{ __tr('Promotions, offres, annonces commerciales et newsletters.') }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="lw-option-card h-100" :class="{ 'selected': category == 'UTILITY' }" @click="category = 'UTILITY'; analyzeTemplateQuality()">
                                            <i class="fas fa-bell text-info"></i>
                                            <div class="lw-option-card-title">{{ __tr('Utilitaire') }}</div>
                                            <div class="text-xs text-muted mt-1">{{ __tr('Mises à jour de compte, rappels, reçus et alertes importantes.') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="lwSelectCategoryField" name="category" x-model="category">
                            </div>

                            <!-- Template Type -->
                            <div class="form-group mb-4">
                                <label class="font-weight-600 text-dark d-block mb-3">{{ __tr('Type de Modèle') }} <span class="text-danger">*</span></label>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="lw-option-card" :class="{ 'selected': templateType == 'header' }" @click="changeTemplateType('header')">
                                            <i class="fas fa-file-alt"></i>
                                            <div class="lw-option-card-title">{{ __tr('Standard') }}</div>
                                            <div class="text-xs text-muted mt-1">{{ __tr('Texte, médias et boutons') }}</div>
                                        </div>
                                        <input type="radio" class="d-none" name="template_type" value="header" x-model="templateType">
                                    </div>
                                    <div class="col-6">
                                        <div class="lw-option-card" :class="{ 'selected': templateType == 'carousel' }" @click="changeTemplateType('carousel')">
                                            <i class="fas fa-images"></i>
                                            <div class="lw-option-card-title">{{ __tr('Carousel') }}</div>
                                            <div class="text-xs text-muted mt-1">{{ __tr('Cartes défilantes') }}</div>
                                        </div>
                                        <input type="radio" class="d-none" name="template_type" value="carousel" x-model="templateType">
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-secondary mb-4">
                                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                                    <div class="mb-3 mb-md-0 mr-md-3">
                                        <i class="fas fa-info-circle mr-1"></i> {{  __tr('While Authentication and Flow templates are supported for sending however you need to create/edit those templates on Meta.') }} 
                                    </div>
                                    <div>
                                        <a class="btn btn-sm btn-light text-nowrap shadow-sm border" target="_blank" href="https://business.facebook.com/wa/manage/message-templates/?waba_id={{ getVendorSettings('whatsapp_business_account_id') }}" > 
                                            {{ __tr('Manage Templates on Meta') }} <i class="fas fa-external-link-alt ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Template Name -->
                            <div class="form-group mb-4">
                                <label for="lwTemplateNameField" class="font-weight-600 text-dark">{{ __tr('Template Name') }} <span class="text-danger">*</span></label>
                                <input type="text" id="lwTemplateNameField" class="form-control form-control-alternative" 
                                       placeholder="ex: code_promo_2026" name="template_name" x-model="templateName" 
                                       @input="sanitizeInput();" @keydown="isValidKey($event)" required>
                                <small class="form-text text-muted">
                                    {{ __tr('Seuls les caractères minuscules (a-z), les chiffres (0-9) et le tiret bas (_) sont autorisés.') }}
                                </small>
                            </div>

                            <!-- Template Language -->
                            <div class="form-group mb-2">
                                <label for="lwSelectLanguage" class="font-weight-600 text-dark">{{ __tr('Template Language Code') }} <span class="text-danger">*</span></label>
                                <select id="lwSelectLanguage" class="form-control" name="language_code" x-model="languageCode" required>
                                    <option value="">{{ __tr('Select Template Language ...') }}</option>
                                    @if(!__isEmpty($languages))
                                        @foreach($languages as $key => $language)
                                            <option value="{{ $language['code'] }}">{{ $language['language'] }} ({{ $language['code'] }})</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                        </div>
                        <div class="card-footer bg-white d-flex justify-content-end">
                            <button type="button" class="btn btn-primary" @click="goToStep(2)">
                                {{ __tr('Suivant') }} <i class="fa fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Header (Standard Only) -->
                <div x-show="step == 2 && templateType == 'header'">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom-0 pb-0">
                            <h3 class="font-weight-bold text-dark mb-0">{{ __tr('Étape 2: En-tête (Optionnel)') }}</h3>
                            <p class="text-muted text-sm mt-1">{{ __tr('Ajoutez un en-tête en texte ou média (image, vidéo, document) en haut de votre message.') }}</p>
                        </div>
                        <div class="card-body">
                            <label class="font-weight-600 text-dark d-block mb-3">{{ __tr('Header Type') }}</label>
                            
                            <div class="row mb-4">
                                <div class="col-4 col-sm-2 mb-2">
                                    <div class="lw-option-card" :class="{ 'selected': headerType == '0' || headerType == '' }" @click="headerType = '0'">
                                        <i class="fas fa-ban"></i>
                                        <div class="lw-option-card-title">{{ __tr('Aucun') }}</div>
                                    </div>
                                    <input type="radio" class="d-none" name="media_header_type" value="0" :checked="headerType == '0'">
                                </div>
                                <div class="col-4 col-sm-2 mb-2">
                                    <div class="lw-option-card" :class="{ 'selected': headerType == 'text' }" @click="headerType = 'text'">
                                        <i class="fas fa-font"></i>
                                        <div class="lw-option-card-title">{{ __tr('Texte') }}</div>
                                    </div>
                                    <input type="radio" class="d-none" name="media_header_type" value="text" x-model="headerType">
                                </div>
                                <div class="col-4 col-sm-2 mb-2">
                                    <div class="lw-option-card" :class="{ 'selected': headerType == 'image' }" @click="headerType = 'image'">
                                        <i class="fas fa-image"></i>
                                        <div class="lw-option-card-title">{{ __tr('Image') }}</div>
                                    </div>
                                    <input type="radio" class="d-none" name="media_header_type" value="image" x-model="headerType">
                                </div>
                                <div class="col-4 col-sm-2 mb-2">
                                    <div class="lw-option-card" :class="{ 'selected': headerType == 'video' }" @click="headerType = 'video'">
                                        <i class="fas fa-video"></i>
                                        <div class="lw-option-card-title">{{ __tr('Vidéo') }}</div>
                                    </div>
                                    <input type="radio" class="d-none" name="media_header_type" value="video" x-model="headerType">
                                </div>
                                <div class="col-4 col-sm-2 mb-2">
                                    <div class="lw-option-card" :class="{ 'selected': headerType == 'document' }" @click="headerType = 'document'">
                                        <i class="fas fa-file-pdf"></i>
                                        <div class="lw-option-card-title">{{ __tr('Doc') }}</div>
                                    </div>
                                    <input type="radio" class="d-none" name="media_header_type" value="document" x-model="headerType">
                                </div>
                                <div class="col-4 col-sm-2 mb-2">
                                    <div class="lw-option-card" :class="{ 'selected': headerType == 'location' }" @click="headerType = 'location'">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <div class="lw-option-card-title">{{ __tr('Carte') }}</div>
                                    </div>
                                    <input type="radio" class="d-none" name="media_header_type" value="location" x-model="headerType">
                                </div>
                            </div>

                            <!-- Header Text configuration -->
                            <div x-show="headerType == 'text'" class="form-group">
                                <label for="lwHeaderTextBody" class="font-weight-600 text-dark">{{ __tr('Header Text') }}</label>
                                <input type="text" id="lwHeaderTextBody" class="form-control" 
                                       placeholder="ex: Notre offre exclusive" name="header_text_body" x-model="header_text_body">
                                
                                <div class="text-right mt-2">
                                    <button :disabled="enableHeaderVariableExample" type="button" class="btn btn-sm btn-dark" @click="addHeaderPlaceholder()">
                                        <i class="fa fa-plus mr-1"></i> {{ __tr('Add Variable') }}
                                    </button>
                                </div>

                                <template x-if="enableHeaderVariableExample">
                                    <div class="form-group mt-3">
                                        <label for="lwHeaderTextBodyExample" class="font-weight-600 text-dark">{{ __tr('Header Text Variable Example') }} <span class="text-danger">*</span></label>
                                        <input type="text" id="lwHeaderTextBodyExample" class="form-control" 
                                               placeholder="ex: Client" name="example_header_fields" x-model="headerVariableExample" required>
                                    </div>
                                </template>
                            </div>

                            <!-- Filepond media uploaders (only visible if related media type selected) -->
                            <div class="my-3">
                                <!-- Document -->
                                <div x-show="headerType == 'document'" class="form-group">
                                    <label class="font-weight-600 text-dark">{{ __tr("Charger le document d'exemple") }} <span class="text-danger">*</span></label>
                                    <input id="lwDocumentMediaFilepond" type="file" data-allow-revert="true"
                                        data-label-idle="{{ __tr('Sélectionnez un document PDF') }}" class="lw-file-uploader"
                                        data-instant-upload="true"
                                        data-action="<?= route('media.upload_temp_media', 'whatsapp_document') ?>"
                                        data-file-input-element="#lwMediaFileName"
                                        data-allowed-media='<?= getMediaRestriction('whatsapp_document') ?>' />
                                </div>
                                <!-- Image -->
                                <div x-show="headerType == 'image'" class="form-group">
                                    <label class="font-weight-600 text-dark">{{ __tr("Charger l'image d'exemple") }} <span class="text-danger">*</span></label>
                                    <input id="lwImageMediaFilepond" type="file" data-allow-revert="true"
                                        data-label-idle="{{ __tr('Sélectionnez une image (PNG/JPG)') }}" class="lw-file-uploader"
                                        data-instant-upload="true"
                                        data-action="<?= route('media.upload_temp_media', 'whatsapp_image') ?>"
                                        data-file-input-element="#lwMediaFileName"
                                        data-allowed-media='<?= getMediaRestriction('whatsapp_image') ?>' />
                                </div>
                                <!-- Video -->
                                <div x-show="headerType == 'video'" class="form-group">
                                    <label class="font-weight-600 text-dark">{{ __tr("Charger la vidéo d'exemple") }} <span class="text-danger">*</span></label>
                                    <input id="lwVideoMediaFilepond" type="file" data-allow-revert="true"
                                        data-label-idle="{{ __tr('Sélectionnez une vidéo (MP4)') }}" class="lw-file-uploader"
                                        data-instant-upload="true"
                                        data-action="<?= route('media.upload_temp_media', 'whatsapp_video') ?>"
                                        data-file-input-element="#lwMediaFileName"
                                        data-allowed-media='<?= getMediaRestriction('whatsapp_video') ?>' />
                                </div>
                                <!-- Location -->
                                <div x-show="headerType == 'location'" class="form-group">
                                    <div class="alert alert-secondary text-sm mb-0">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        {{ __tr("La localisation est un en-tête dynamique. Vous n'avez pas besoin de fournir de coordonnées ici. Les détails (latitude, longitude, adresse) seront spécifiés au moment de l'envoi du message.") }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" @click="goToStep(1)">
                                <i class="fa fa-arrow-left mr-1"></i> {{ __tr('Précédent') }}
                            </button>
                            <button type="button" class="btn btn-primary" @click="goToStep(3)">
                                {{ __tr('Suivant') }} <i class="fa fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Body (Standard Only) -->
                <div x-show="step == 3 && templateType == 'header'">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom-0 pb-0">
                            <h3 class="font-weight-bold text-dark mb-0">{{ __tr('Étape 3: Corps du message') }}</h3>
                            <p class="text-muted text-sm mt-1">{{ __tr('Rédigez le message principal. Vous pouvez formater le texte et insérer des variables.') }}</p>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="lwTemplateBody" class="font-weight-600 text-dark">{{ __tr('Body Text') }} <span class="text-danger">*</span></label>
                                <textarea name="template_body" id="lwTemplateBody" class="form-control" rows="8" 
                                          placeholder="Bonjour @{{1}}, voici votre code de réduction de @{{2}}%..." 
                                          x-model="text_body" @input="updateBodyVariables()" required></textarea>
                            </div>

                            <!-- Formatting buttons -->
                            <div class="form-group text-right">
                                <button type="button" class="btn btn-light btn-sm font-weight-bold" title="{{ __tr('Gras') }}" @click="wrapBodyText('*')">
                                    <i class="fa fa-bold"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-sm font-italic" title="{{ __tr('Italique') }}" @click="wrapBodyText('_')">
                                    <i class="fa fa-italic"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-sm" style="text-decoration: line-through;" title="{{ __tr('Barré') }}" @click="wrapBodyText('~')">
                                    <i class="fa fa-strikethrough"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-sm font-mono" title="{{ __tr('Police de code') }}" @click="wrapBodyText('```')">
                                    <i class="fa fa-code"></i>
                                </button>
                                <button type="button" class="btn btn-dark btn-sm" @click="addBodyPlaceholder()">
                                    <i class="fa fa-plus mr-1"></i> {{ __tr('Ajouter une variable') }}
                                </button>
                                <i class="fas fa-info-circle text-muted ml-2" data-toggle="tooltip" title="{{ __tr('Ajoute une balise') }} @{{1}} {{ __tr('pour y insérer un texte dynamique plus tard (prénom, code promo...)') }}" style="cursor: help;"></i>
                            </div>

                            <!-- Dynamic Examples Fields for Body Variables -->
                            <div class="mt-4" x-show="Object.keys(newBodyTextInputFields).length > 0">
                                <h4 class="font-weight-600 text-dark mb-3">{{ __tr('Exemples des variables') }}</h4>
                                <p class="text-muted text-xs mb-3">{{ __tr("Renseignez des valeurs réalistes d'exemple pour la validation par Meta.") }}</p>
                                
                                <template x-for="(item, index) in newBodyTextInputFields" :key="index">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text font-weight-bold" x-text="item.varName"></span>
                                            </div>
                                            <input type="text" class="form-control"
                                                   x-bind:name="'example_body_fields[' + index + ']'"
                                                   placeholder="ex: Jean"
                                                   x-model="bodyVariablesData[index]"
                                                   @input="updateBodyVariables()"
                                                   required>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" @click="goToStep(2)">
                                <i class="fa fa-arrow-left mr-1"></i> {{ __tr('Précédent') }}
                            </button>
                            <button type="button" class="btn btn-primary" @click="goToStep(4)">
                                {{ __tr('Suivant') }} <i class="fa fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Footer (Standard Only) -->
                <div x-show="step == 4 && templateType == 'header'">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom-0 pb-0">
                            <h3 class="font-weight-bold text-dark mb-0">{{ __tr('Étape 4: Pied de page (Optionnel)') }}</h3>
                            <p class="text-muted text-sm mt-1">{{ __tr('Ajoutez une courte ligne de texte en bas du message (en petits caractères gris).') }}</p>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="lwTemplateFooter" class="font-weight-600 text-dark">{{ __tr('Footer (Optional)') }}</label>
                                <input type="text" id="lwTemplateFooter" class="form-control" 
                                       placeholder="ex: Envoyé par Ma Boutique. Pour vous désabonner répondez STOP." 
                                       name="template_footer" x-model="footer_text_body" maxlength="60">
                                <small class="form-text text-muted text-right">
                                    <span x-text="60 - (footer_text_body ? footer_text_body.length : 0)"></span> / 60 {{ __tr('caractères restants') }}
                                </small>
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" @click="goToStep(3)">
                                <i class="fa fa-arrow-left mr-1"></i> {{ __tr('Précédent') }}
                            </button>
                            <button type="button" class="btn btn-primary" @click="goToStep(5)">
                                {{ __tr('Suivant') }} <i class="fa fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Buttons (Standard Only) -->
                <div x-show="step == 5 && templateType == 'header'">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom-0 pb-0">
                            <h3 class="font-weight-bold text-dark mb-0">{{ __tr("Étape 5: Boutons d'Action (Optionnel)") }}</h3>
                            <p class="text-muted text-sm mt-1">{{ __tr('Créez des boutons cliquables pour inciter vos clients à répondre ou visiter un lien.') }}</p>
                        </div>
                        <div class="card-body">
                            <!-- Existing buttons list container -->
                            <div class="lw-buttons-container mb-4">
                                <template x-for="(btn, index) in customButtons" :key="btn.id">
                                    <div class="card shadow-none border mb-3">
                                        <div class="card-header p-2 bg-white border-bottom d-flex justify-content-between align-items-center">
                                            <span class="font-weight-bold text-sm text-primary">
                                                <i class="fas fa-circle mr-1 text-xs"></i>
                                                <span x-text="getButtonTypeText(btn.type)"></span>
                                            </span>
                                            <button type="button" class="btn btn-link text-danger p-1 m-0" @click="deleteButton(index)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                        <div class="card-body p-3">
                                            <!-- Hidden input to submit the button type -->
                                            <input type="hidden" :name="'message_buttons['+btn.id+'][type]'" :value="btn.type">

                                            <!-- Button label (All types except Copy Code sometimes need it) -->
                                            <div class="form-group mb-2">
                                                <label class="font-weight-600 text-xs text-dark mb-1">{{ __tr('Texte du bouton') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm" required maxlength="25"
                                                       placeholder="ex: Visiter le site"
                                                       :name="'message_buttons['+btn.id+'][text]'" x-model="btn.text">
                                                <small class="form-text text-muted text-xs text-right">
                                                    <span x-text="25 - (btn.text ? btn.text.length : 0)"></span> / 25
                                                </small>
                                            </div>

                                            <!-- Phone Number Button fields -->
                                            <div x-show="btn.type == 'PHONE_NUMBER'">
                                                <div class="form-group mb-2">
                                                    <label class="font-weight-600 text-xs text-dark mb-1">{{ __tr('Numéro de téléphone (avec indicatif pays)') }} <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control form-control-sm" placeholder="ex: 33612345678"
                                                           :name="'message_buttons['+btn.id+'][phone_number]'" x-model="btn.phone_number"
                                                           :required="btn.type == 'PHONE_NUMBER'">
                                                </div>
                                            </div>

                                            <!-- URL Button fields (Static Website) -->
                                            <div x-show="btn.type == 'URL_BUTTON'">
                                                <div class="form-group mb-2">
                                                    <label class="font-weight-600 text-xs text-dark mb-1">{{ __tr('Website URL') }} <span class="text-danger">*</span></label>
                                                    <input type="url" class="form-control form-control-sm" placeholder="ex: https://maboutique.com/promo"
                                                           :name="'message_buttons['+btn.id+'][url]'" x-model="btn.url"
                                                           :required="btn.type == 'URL_BUTTON'">
                                                </div>
                                            </div>

                                            <!-- Dynamic URL Button fields -->
                                            <div x-show="btn.type == 'DYNAMIC_URL_BUTTON'">
                                                <div class="form-group mb-2">
                                                    <label class="font-weight-600 text-xs text-dark mb-1">
                                                        {{ __tr('Website URL') }} <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="url" class="form-control" placeholder="ex: https://maboutique.com/suivi"
                                                               :name="'message_buttons['+btn.id+'][url]'" x-model="btn.url"
                                                               :required="btn.type == 'DYNAMIC_URL_BUTTON'">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text font-weight-bold">@{{1}}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-2">
                                                    <label class="font-weight-600 text-xs text-dark mb-1">{{ __tr("Valeur d'exemple de la variable URL") }} <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control form-control-sm" placeholder="ex: commande-1234"
                                                           :name="'message_buttons['+btn.id+'][example]'" x-model="btn.example"
                                                           :required="btn.type == 'DYNAMIC_URL_BUTTON'">
                                                </div>
                                            </div>

                                            <!-- Copy Code Coupon Button fields -->
                                            <div x-show="btn.type == 'COPY_CODE'">
                                                <div class="form-group mb-2">
                                                    <label class="font-weight-600 text-xs text-dark mb-1">{{ __tr('Code promo à copier') }} <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control form-control-sm" placeholder="ex: ETE2026"
                                                           :name="'message_buttons['+btn.id+'][example]'" x-model="btn.example"
                                                           :required="btn.type == 'COPY_CODE'">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Button Options add panel -->
                            <div class="mt-3 p-3 border rounded bg-white">
                                <h4 class="font-weight-600 text-dark mb-2 text-sm">{{ __tr("Ajouter un bouton d'action") }}</h4>
                                <div class="d-flex flex-wrap gap-2" style="gap: 8px;">
                                    <button type="button" class="btn btn-outline-primary btn-sm mb-2" 
                                            :disabled="customButtons.length >= 10"
                                            @click="addButton('QUICK_REPLY')">
                                        <i class="fa fa-reply mr-1"></i> {{ __tr('Quick Reply') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm mb-2"
                                            :disabled="customButtons.length >= 10 || hasButtonType('PHONE_NUMBER')"
                                            @click="addButton('PHONE_NUMBER')">
                                        <i class="fa fa-phone-alt mr-1"></i> {{ __tr('Call Phone') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm mb-2"
                                            :disabled="customButtons.length >= 10 || getUrlButtonCount() >= 2"
                                            @click="addButton('URL_BUTTON')">
                                        <i class="fa fa-link mr-1"></i> {{ __tr('Website Link') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm mb-2"
                                            :disabled="customButtons.length >= 10 || getUrlButtonCount() >= 2"
                                            @click="addButton('DYNAMIC_URL_BUTTON')">
                                        <i class="fa fa-link mr-1"></i> {{ __tr('Dynamic Link') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm mb-2"
                                            :disabled="customButtons.length >= 10 || hasButtonType('COPY_CODE')"
                                            @click="addButton('COPY_CODE')">
                                        <i class="fa fa-copy mr-1"></i> {{ __tr('Copy Code') }}
                                    </button>
                                </div>
                                <div class="text-xs text-muted mt-2">
                                    {{ __tr("Limites : 1 seul bouton Téléphone, 1 seul bouton Copier Code, max 2 boutons URL. Jusqu'à 10 boutons au total.") }}
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" @click="goToStep(4)">
                                <i class="fa fa-arrow-left mr-1"></i> {{ __tr('Précédent') }}
                            </button>
                            <button type="button" class="btn btn-primary" @click="goToStep(6)">
                                {{ __tr('Suivant (Revue)') }} <i class="fa fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 6: Review (Standard Only) -->
                <div x-show="step == 6 && templateType == 'header'">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom-0 pb-0">
                            <h3 class="font-weight-bold text-dark mb-0">{{ __tr('Étape 6: Revue & Soumission') }}</h3>
                            <p class="text-muted text-sm mt-1">{{ __tr('Vérifiez les informations de votre modèle avant de le soumettre pour approbation à Meta.') }}</p>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-sm">
                                <tbody>
                                    <tr>
                                        <td class="font-weight-bold text-dark w-30">{{ __tr('Template Name') }}</td>
                                        <td x-text="templateName || '-'"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold text-dark">{{ __tr('Language') }}</td>
                                        <td x-text="languageCode || '-'"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold text-dark">{{ __tr('Category') }}</td>
                                        <td x-text="category"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold text-dark">{{ __tr('Header Type') }}</td>
                                        <td x-text="headerType == '0' ? 'Aucun' : headerType.toUpperCase()"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold text-dark">{{ __tr('Footer') }}</td>
                                        <td x-text="footer_text_body || 'Aucun'"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold text-dark">{{ __tr('Boutons') }}</td>
                                        <td x-text="customButtons.length > 0 ? customButtons.length + ' bouton(s)' : 'Aucun'"></td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="alert alert-warning mt-4 text-sm">
                                <i class="fa fa-exclamation-triangle mr-1"></i>
                                {{ __tr("La validation des modèles par Meta peut prendre de quelques minutes à 24 heures. Assurez-vous d'avoir correctement renseigné les exemples pour vos variables.") }}
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" @click="goToStep(5)">
                                <i class="fa fa-arrow-left mr-1"></i> {{ __tr('Précédent') }}
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-paper-plane mr-1"></i> {{ __tr('Submit for Meta Approval') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Carousel General Body -->
                <div x-show="step == 2 && templateType == 'carousel'">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom-0 pb-0">
                            <h3 class="font-weight-bold text-dark mb-0">{{ __tr('Étape 2: Corps Général') }}</h3>
                            <p class="text-muted text-sm mt-1">{{ __tr('Ce texte apparaîtra au-dessus du carrousel de cartes.') }}</p>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="lwCarouselTemplateBody" class="font-weight-600 text-dark">{{ __tr('Texte Principal') }} <span class="text-danger">*</span></label>
                                <textarea name="carousel_template_body" id="lwCarouselTemplateBody" class="form-control" rows="5" 
                                          placeholder="Découvrez nos dernières offres :" 
                                          x-model="carousel_body_text" @input="updateCarouselBodyVariables()" required></textarea>
                            </div>
                            
                            <!-- Formatting buttons -->
                            <div class="form-group text-right">
                                <button type="button" class="btn btn-light btn-sm font-weight-bold" @click="wrapCarouselBodyText('*')"><i class="fa fa-bold"></i></button>
                                <button type="button" class="btn btn-light btn-sm font-italic" @click="wrapCarouselBodyText('_')"><i class="fa fa-italic"></i></button>
                                <button type="button" class="btn btn-light btn-sm" style="text-decoration: line-through;" @click="wrapCarouselBodyText('~')"><i class="fa fa-strikethrough"></i></button>
                                <button type="button" class="btn btn-light btn-sm font-mono" @click="wrapCarouselBodyText('```')"><i class="fa fa-code"></i></button>
                                <button type="button" class="btn btn-dark btn-sm" @click="addCarouselBodyPlaceholder()"><i class="fa fa-plus mr-1"></i> {{ __tr('Ajouter Variable') }}</button>
                            </div>

                            <!-- Variables Examples -->
                            <div class="mt-4" x-show="Object.keys(newCarouselBodyTextInputFields).length > 0">
                                <h4 class="font-weight-600 text-dark mb-3">{{ __tr('Exemples des variables') }}</h4>
                                <template x-for="(item, index) in newCarouselBodyTextInputFields" :key="index">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text font-weight-bold" x-text="item.varName"></span></div>
                                            <input type="text" class="form-control" x-bind:name="'example_body_fields[' + index + ']'" x-model="carouselBodyVariablesData[index]" @input="updateCarouselBodyVariables()" required>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" @click="goToStep(1)"><i class="fa fa-arrow-left mr-1"></i> {{ __tr('Précédent') }}</button>
                            <button type="button" class="btn btn-primary" @click="goToStep(3)">{{ __tr('Suivant') }} <i class="fa fa-arrow-right ml-1"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Carousel Cards -->
                <div x-show="step == 3 && templateType == 'carousel'">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom-0 pb-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="font-weight-bold text-dark mb-0">{{ __tr('Étape 3: Cartes du Carrousel') }}</h3>
                                <p class="text-muted text-sm mt-1 mb-0">{{ __tr("Ajoutez jusqu'à 10 cartes défilantes. Chaque carte nécessite une image/vidéo et un texte.") }}</p>
                            </div>
                            <span class="badge badge-primary badge-pill px-3 py-2 text-sm">
                                <span x-text="carouselTemplateContainer.length"></span> / 10 cartes
                            </span>
                        </div>
                        <div class="card-body bg-white">
                            <template x-for="(card, index) in carouselTemplateContainer" :key="card.id">
                                <div class="card shadow-none border mb-4" :id="card.id">
                                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                        <h4 class="font-weight-bold text-dark mb-0">
                                            <span class="badge badge-dark mr-2" x-text="index + 1"></span>
                                            {{ __tr('Carte') }} <span x-text="index + 1"></span>
                                        </h4>
                                        <button x-show="index > 0" type="button" class="btn btn-sm btn-outline-danger" @click="deleteCard(index)">
                                            <i class="fa fa-trash mr-1"></i> {{ __tr('Supprimer') }}
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <!-- Header Type -->
                                        <div class="form-group mb-4">
                                            <label class="font-weight-600 text-dark">{{ __tr('Média de la carte') }} <span class="text-danger">*</span></label>
                                            <div class="border rounded p-3 bg-light mt-2">
                                                <div class="d-flex mb-3">
                                                    <div class="custom-control custom-radio mr-4">
                                                        <input type="radio" :id="'headerImg' + index" :name="'carousel_templates['+index+'][header_type]'" value="image" class="custom-control-input" x-model="card.headerType">
                                                        <label class="custom-control-label font-weight-normal" :for="'headerImg' + index"><i class="fa fa-image text-muted mr-1"></i> {{ __tr('Image') }}</label>
                                                    </div>
                                                    <div class="custom-control custom-radio">
                                                        <input type="radio" :id="'headerVid' + index" :name="'carousel_templates['+index+'][header_type]'" value="video" class="custom-control-input" x-model="card.headerType">
                                                        <label class="custom-control-label font-weight-normal" :for="'headerVid' + index"><i class="fa fa-video text-muted mr-1"></i> {{ __tr('Vidéo') }}</label>
                                                    </div>
                                                </div>
                                                <!-- Media Uploaders -->
                                                <div x-show="card.headerType == 'image'">
                                                    <input :id="'lwCardImageMediaFilepond'+index" type="file" data-allow-revert="true"
                                                        data-label-idle="<i class='fa fa-cloud-upload-alt mr-2'></i>{{ __tr('Upload Image') }}" class="" data-lw-plugin="lwUploader"
                                                        data-instant-upload="true" data-action="<?= route('media.upload_temp_media', 'whatsapp_image') ?>"
                                                        :data-file-input-element="'#lwCardMediaFileName'+index" data-allowed-media='<?= getMediaRestriction('whatsapp_image') ?>'/>
                                                </div>
                                                <div x-show="card.headerType == 'video'">
                                                    <input :id="'lwCardVideoMediaFilepond'+index" type="file" data-allow-revert="true"
                                                        data-label-idle="<i class='fa fa-cloud-upload-alt mr-2'></i>{{ __tr('Upload Vidéo') }}" class="" data-lw-plugin="lwUploader"
                                                        data-instant-upload="true" data-action="<?= route('media.upload_temp_media', 'whatsapp_video') ?>"
                                                        :data-file-input-element="'#lwCardMediaFileName'+index" data-allowed-media='<?= getMediaRestriction('whatsapp_video') ?>'/>
                                                </div>
                                                <input :id="'lwCardMediaFileName'+index" type="hidden" value="" :name="'carousel_templates['+index+'][uploaded_media_file_name]'" />
                                            </div>
                                        </div>

                                        <!-- Card Body -->
                                        <div class="form-group">
                                            <label :for="'lwCarouselCardBody' + index" class="font-weight-600 text-dark">{{ __tr('Texte de la carte') }} <span class="text-danger">*</span></label>
                                            <textarea :name="'carousel_templates['+index+'][carousel_card_body]'" :id="'lwCarouselCardBody' + index" class="form-control" rows="3" x-model="card.bodyText" @input="updateCardVariables(index)" required></textarea>
                                        </div>
                                        
                                        <!-- Formatting buttons -->
                                        <div class="form-group text-right">
                                            <button type="button" class="btn btn-light btn-sm" @click="wrapCardBodyText(index, '*')"><i class="fa fa-bold"></i></button>
                                            <button type="button" class="btn btn-light btn-sm" @click="wrapCardBodyText(index, '_')"><i class="fa fa-italic"></i></button>
                                            <button type="button" class="btn btn-light btn-sm" @click="wrapCardBodyText(index, '~')"><i class="fa fa-strikethrough"></i></button>
                                            <button type="button" class="btn btn-dark btn-sm" @click="addCardBodyPlaceholder(index)"><i class="fa fa-plus"></i></button>
                                        </div>

                                        <!-- Card Variables -->
                                        <div class="mt-3" x-show="Object.keys(card.variablesInputs).length > 0">
                                            <template x-for="(item, vIndex) in card.variablesInputs" :key="vIndex">
                                                <div class="form-group">
                                                    <div class="input-group input-group-sm">
                                                        <div class="input-group-prepend"><span class="input-group-text font-weight-bold" x-text="item.varName"></span></div>
                                                        <input type="text" class="form-control" :name="'carousel_templates['+index+'][body_example_fields]['+vIndex+']'" x-model="card.variablesData[vIndex]" @input="updateCardVariables(index)" required>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Card Buttons -->
                                        <div class="mt-4 pt-3 border-top">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <label class="font-weight-600 text-dark mb-0">{{ __tr('Buttons (Max 2)') }}</label>
                                                <button type="button" class="btn btn-sm btn-outline-primary" @click.prevent="addCardButton(index, 'QUICK_REPLY')" :disabled="card.buttons && card.buttons.length >= 2">
                                                    <i class="fa fa-plus"></i> {{ __tr('Ajouter') }}
                                                </button>
                                            </div>
                                            
                                            <div x-show="card.buttons && card.buttons.length > 0">
                                                <template x-for="(btn, bIndex) in card.buttons" :key="btn.id">
                                                    <div class="border rounded p-3 mb-3 bg-white shadow-sm position-relative">
                                                        <div class="d-flex justify-content-between mb-3 align-items-center">
                                                            <div class="d-flex flex-wrap align-items-center" style="gap: 15px;">
                                                                <div class="custom-control custom-radio">
                                                                    <input type="radio" :id="'btnQR_'+index+'_'+bIndex" class="custom-control-input" value="QUICK_REPLY" :name="'carousel_templates['+index+'][message_buttons]['+bIndex+'][type]'" x-model="btn.type" @change="changeCardButtonType(index, bIndex, 'QUICK_REPLY')">
                                                                    <label class="custom-control-label font-weight-normal text-sm" :for="'btnQR_'+index+'_'+bIndex"><i class="fa fa-reply text-muted mr-1"></i> Quick Reply</label>
                                                                </div>
                                                                <div class="custom-control custom-radio">
                                                                    <input type="radio" :id="'btnURL_'+index+'_'+bIndex" class="custom-control-input" value="URL_BUTTON" :name="'carousel_templates['+index+'][message_buttons]['+bIndex+'][type]'" x-model="btn.type" @change="changeCardButtonType(index, bIndex, 'URL_BUTTON')">
                                                                    <label class="custom-control-label font-weight-normal text-sm" :for="'btnURL_'+index+'_'+bIndex"><i class="fa fa-link text-muted mr-1"></i> URL</label>
                                                                </div>
                                                                <div class="custom-control custom-radio">
                                                                    <input type="radio" :id="'btnPhone_'+index+'_'+bIndex" class="custom-control-input" value="PHONE_NUMBER" :name="'carousel_templates['+index+'][message_buttons]['+bIndex+'][type]'" x-model="btn.type" @change="changeCardButtonType(index, bIndex, 'PHONE_NUMBER')">
                                                                    <label class="custom-control-label font-weight-normal text-sm" :for="'btnPhone_'+index+'_'+bIndex"><i class="fa fa-phone-alt text-muted mr-1"></i> Phone</label>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 ml-2" @click="deleteCardButton(index, bIndex)">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </div>
                                                        
                                                        <div class="row">
                                                            <div class="col-md-6 mb-2 mb-md-0">
                                                                <input type="text" class="form-control form-control-sm" placeholder="Button text..." maxlength="25" :name="'carousel_templates['+index+'][message_buttons]['+bIndex+'][text]'" x-model="btn.text" @input="analyzeTemplateQuality()" required>
                                                                <div class="text-right text-muted" style="font-size: 11px;"><span x-text="btn.text ? btn.text.length : 0"></span>/25</div>
                                                            </div>
                                                            <div class="col-md-6" x-show="btn.type == 'URL_BUTTON'">
                                                                <input type="url" class="form-control form-control-sm" placeholder="https://example.com" :name="'carousel_templates['+index+'][message_buttons]['+bIndex+'][url]'" x-model="btn.url" @input="analyzeTemplateQuality()" :required="btn.type == 'URL_BUTTON'">
                                                            </div>
                                                            <div class="col-md-6" x-show="btn.type == 'PHONE_NUMBER'">
                                                                <input type="tel" class="form-control form-control-sm" placeholder="+1234567890" :name="'carousel_templates['+index+'][message_buttons]['+bIndex+'][phone_number]'" x-model="btn.phone_number" @input="analyzeTemplateQuality()" :required="btn.type == 'PHONE_NUMBER'">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            <!-- Hidden input to pass the count of buttons to backend -->
                                            <input type="hidden" :name="'carousel_templates['+index+'][message_buttons_count]'" :value="card.buttons ? card.buttons.length : 0">
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <div class="text-center mt-4 mb-2">
                                <button type="button" class="btn btn-dark" @click="addNewCard()" :disabled="carouselTemplateContainer.length >= 10">
                                    <i class="fa fa-plus mr-1"></i> {{ __tr('Ajouter une carte') }}
                                </button>
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" @click="goToStep(2)"><i class="fa fa-arrow-left mr-1"></i> {{ __tr('Précédent') }}</button>
                            <button type="button" class="btn btn-primary" @click="goToStep(4)">{{ __tr('Suivant') }} <i class="fa fa-arrow-right ml-1"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Carousel Review -->
                <div x-show="step == 4 && templateType == 'carousel'">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom-0 pb-0">
                            <h3 class="font-weight-bold text-dark mb-0">{{ __tr('Étape 4: Revue & Soumission') }}</h3>
                            <p class="text-muted text-sm mt-1">{{ __tr('Vérifiez les paramètres de votre carrousel avant de le soumettre.') }}</p>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-sm">
                                <tbody>
                                    <tr><td class="font-weight-bold w-50">{{ __tr('Nom') }}</td><td x-text="templateName"></td></tr>
                                    <tr><td class="font-weight-bold">{{ __tr('Catégorie') }}</td><td x-text="category"></td></tr>
                                    <tr><td class="font-weight-bold">{{ __tr('Cartes') }}</td><td x-text="carouselTemplateContainer.length"></td></tr>
                                </tbody>
                            </table>
                            <div class="alert alert-warning mt-4 text-sm">
                                <i class="fa fa-exclamation-triangle mr-1"></i> {{ __tr("La validation peut prendre jusqu'à 24h.") }}
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" @click="goToStep(3)"><i class="fa fa-arrow-left mr-1"></i> {{ __tr('Précédent') }}</button>
                            <button type="submit" class="btn btn-success"><i class="fa fa-paper-plane mr-1"></i> {{ __tr('Submit') }}</button>
                        </div>
                    </div>
                </div>

            </x-lw.form>
        </div>

        <!-- WhatsApp Phone Live Preview Column (right side) -->
        <div class="col-lg-4">
            
            <!-- Meta Validation Score Placeholder -->
            <div class="card shadow-sm mb-3 d-none d-lg-block">
                <div class="card-body p-3 text-center">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="font-weight-bold text-dark mb-0">
                            {{ __tr('Score de Validation Meta') }}
                            <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="{{ __tr('L\'approbation finale dépend uniquement de Meta. Ce score est un estimateur basé sur leurs directives officielles.') }}"></i>
                        </h6>
                        <span class="badge" :class="'badge-' + metaScoreColor.replace('bg-', '')" x-text="metaScore + '%'"></span>
                    </div>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar" :class="metaScoreColor" role="progressbar" :style="'width: ' + metaScore + '%;'" :aria-valuenow="metaScore" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted mt-2 d-block" style="line-height: 1.2;" x-html="metaScoreMessage"></small>
                </div>
            </div>
            <div class="lw-phone-preview-wrapper">
                <h3 class="text-center font-weight-bold text-dark mb-3">
                    <i class="fab fa-whatsapp text-success mr-1"></i> {{ __tr('Direct Live Preview') }}
                </h3>
                
                <!-- Phone mockup structure -->
                <div class="lw-phone-container">
                    <div class="lw-phone-header">
                        <div class="lw-phone-avatar">B</div>
                        <div>
                            <div class="lw-phone-username">{{ __tr('Ma Boutique Business') }}</div>
                            <small class="text-success text-xs" style="opacity: 0.8;">{{ __tr('En ligne') }}</small>
                        </div>
                    </div>
                    
                    <div class="lw-phone-chat-area">
                        <!-- Message Bubble -->
                        
                <!-- PREVIEW STANDARD HEADER TYPE -->
                <div x-show="templateType == 'header'" class="lw-wa-bubble">
                    <!-- Header -->
                    <div x-show="headerType != '0'" class="mb-2">
                        <div x-show="headerType == 'text'" class="lw-wa-header-text">
                            <span x-html="formatWhatsAppHeaderPreview(header_text_body)"></span>
                        </div>
                        <div x-show="headerType == 'image'" class="lw-wa-header-media">
                            <i class="fa fa-image text-muted"></i>
                        </div>
                        <div x-show="headerType == 'video'" class="lw-wa-header-media">
                            <i class="fa fa-video text-muted"></i>
                        </div>
                        <div x-show="headerType == 'document'" class="lw-wa-header-media">
                            <i class="fa fa-file-pdf text-muted"></i>
                        </div>
                        <div x-show="headerType == 'location'" class="lw-wa-header-media">
                            <i class="fa fa-map-marker-alt text-muted"></i>
                        </div>
                    </div>
                    
                    <!-- Body -->
                    <div class="lw-wa-body-text" x-html="formatWhatsAppPreview(text_body) || '<em>Votre message apparaîtra ici...</em>'"></div>
                    
                    <!-- Footer -->
                    <div x-show="footer_text_body" class="lw-wa-footer-text" x-text="footer_text_body"></div>
                    
                    <!-- Buttons -->
                    <div class="lw-wa-buttons-area" x-show="customButtons.length > 0">
                        <template x-for="btn in customButtons" :key="btn.id">
                            <div class="lw-wa-btn">
                                <i class="fa fa-reply" x-show="btn.type == 'QUICK_REPLY'"></i>
                                <i class="fa fa-phone-alt" x-show="btn.type == 'PHONE_NUMBER'"></i>
                                <i class="fa fa-external-link-alt" x-show="btn.type == 'URL_BUTTON' || btn.type == 'DYNAMIC_URL_BUTTON'"></i>
                                <i class="fa fa-copy" x-show="btn.type == 'COPY_CODE'"></i>
                                <span x-text="btn.text ? btn.text : 'Bouton'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- PREVIEW CAROUSEL TYPE -->
                <div x-show="templateType == 'carousel'" style="width: 100%;">
                    <!-- General Body -->
                    <div x-show="carousel_body_text" class="lw-wa-bubble mb-2" style="max-width: 95%;">
                        <div class="lw-wa-body-text" x-html="formatCarouselBodyPreview(carousel_body_text)"></div>
                    </div>
                    
                    <!-- Cards Container -->
                    <div class="d-flex pb-2" style="overflow-x: auto; gap: 8px; max-width: 100%;">
                        <template x-for="(card, index) in carouselTemplateContainer" :key="card.id">
                            <div class="lw-wa-bubble flex-shrink-0" style="width: 220px; max-width: none;">
                                <!-- Header Media -->
                                <div class="lw-wa-header-media" style="height: 100px;">
                                    <i class="fa fa-image text-muted" x-show="card.headerType == 'image'"></i>
                                    <i class="fa fa-video text-muted" x-show="card.headerType == 'video'"></i>
                                </div>
                                <!-- Card Body -->
                                <div class="lw-wa-body-text" x-html="formatCardBodyPreview(index) || '<em>Texte de la carte...</em>'"></div>
                                <!-- Buttons -->
                                <div class="lw-wa-buttons-area mt-2" x-show="card.buttons && card.buttons.length > 0">
                                    <template x-for="btn in card.buttons" :key="btn.id">
                                        <div class="lw-wa-btn">
                                            <i class="fa fa-reply" x-show="btn.type == 'QUICK_REPLY'"></i>
                                            <i class="fa fa-phone-alt" x-show="btn.type == 'PHONE_NUMBER'"></i>
                                            <i class="fa fa-external-link-alt" x-show="btn.type == 'URL_BUTTON' || btn.type == 'DYNAMIC_URL_BUTTON'"></i>
                                            <span x-text="btn.text ? btn.text : 'Bouton'"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


@endsection


