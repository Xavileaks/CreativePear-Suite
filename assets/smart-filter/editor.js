(() => {
    function register() {
        const editor = window.elementor;
        const Select = editor?.getControlView('select');
        if (!Select) return;
        const strings = window.xwSmartFilterEditor;
        editor.addControlView('xw_sf_loop', Select.extend({
            onReady() {
                const select = this.ui.select;
                const settings = this.container?.settings || this.elementSettingsModel;
                const refresh = () => {
                    const current = this.getControlValue() || '';
                    const template = String(settings?.get('loop_template') || '');
                    const items = [];
                    function collect(models) {
                        for (const model of models || []) {
                            const skin = model.getSetting?.('_skin');
                            const source = model.getSetting?.('post_query_post_type');
                            const products = skin === 'product' || (skin === 'post' && ['product', 'current_query'].includes(source));
                            if (model.get('widgetType') === 'loop-grid' && products && template &&
                                String(model.getSetting('template_id')) === template) {
                                items.push({
                                    id: 'elementor:' + model.id,
                                    title: model.getTitle?.() || 'Loop Grid',
                                    css: model.getSetting?.('_element_id')
                                });
                            }
                            collect(model.get('elements')?.models);
                        }
                    }
                    collect(editor.getPreviewView()?.collection?.models);
                    this.$el.toggle(items.length > 1);
                    select.empty().append(new Option(strings.choose, ''));
                    const counts = new Map();
                    for (const item of items) counts.set(item.title, (counts.get(item.title) || 0) + 1);
                    items.forEach((item, index) => {
                        const title = counts.get(item.title) > 1 ? item.title + ' · ' + (item.css || index + 1) : item.title;
                        select.append(new Option(title, item.id));
                    });
                    if (current && !items.some(item => item.id === current)) {
                        select.append(new Option(strings.missing, current));
                    }
                    select.val(current);
                };
                refresh();
                if (settings) this.listenTo(settings, 'change:loop_template', refresh);
                select.on('focus.xwSf pointerdown.xwSf', refresh);
            }
        }));
    }
    window.jQuery(window).one('elementor:init', register);
})();
