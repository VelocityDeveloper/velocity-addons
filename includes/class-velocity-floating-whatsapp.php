<?php

/**
 * Register Floating Whatsapp in the WordPress admin panel
 *
 * @link       https://velocitydeveloper.com
 * @since      1.0.0
 *
 * @package    Velocity_Addons
 * @subpackage Velocity_Addons/includes
 */

class Velocity_Addons_Floating_Whatsapp
{
    public function __construct()
    {
        $floating_whatsapp = get_option('floating_whatsapp', '1');
        if ($floating_whatsapp !== '1')
            return false;

        $this->maybe_migrate_whatsapp_options();

        // Menambahkan submenu
        add_action('admin_init', [$this, 'register_wafloat_settings']);
    }

    public function register_wafloat_settings()
    {
        register_setting('velocity_floating_whatsapp_group', 'nomor_whatsapp_contacts', [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_whatsapp_contacts'],
        ]);
        register_setting('velocity_floating_whatsapp_group', 'nomor_whatsapp', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting('velocity_floating_whatsapp_group', 'whatsapp_message', [
            'type'              => 'string',
            'sanitize_callback' => [$this, 'sanitize_whatsapp_message'],
        ]);
        register_setting('velocity_floating_whatsapp_group', 'whatsapp_text');
        register_setting('velocity_floating_whatsapp_group', 'whatsapp_position');
    }

    public static function floating_whatsapp_page()
    {
        $current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : 'velocity_floating_whatsapp';
        $is_style_page = $current_page === 'velocity_floating_whatsapp_style';

        $contacts = self::normalize_whatsapp_contacts(get_option('nomor_whatsapp_contacts', []));
        if (empty($contacts)) {
            $contacts = [['name' => '', 'number' => '']];
        }
        $whatsapp_text    = get_option('whatsapp_text', 'Butuh Bantuan?');
        $whatsapp_message = get_option('whatsapp_message', 'Hallo...');
?>
        <div class="velocity-dashboard-wrapper">
            <?php Velocity_Addons_Admin_Navigation::render($current_page); ?>
            <form method="post" action="options.php" data-velocity-settings="1">
                <?php settings_fields('velocity_floating_whatsapp_group'); ?>
                <?php if ($is_style_page) : ?>
                    <?php foreach ($contacts as $index => $contact) : ?>
                        <input type="hidden" name="nomor_whatsapp_contacts[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($contact['name'] ?? ''); ?>" />
                        <input type="hidden" name="nomor_whatsapp_contacts[<?php echo esc_attr($index); ?>][number]" value="<?php echo esc_attr($contact['number'] ?? ''); ?>" />
                    <?php endforeach; ?>
                    <input type="hidden" name="whatsapp_text" value="<?php echo esc_attr($whatsapp_text); ?>" />
                    <input type="hidden" name="whatsapp_message" value="<?php echo esc_attr($whatsapp_message); ?>" />
                    <div class="vd-section">
                        <div class="vd-section-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; background-color: #f9fafb;">
                            <h3 style="margin:0; font-size:1.1rem; color:#374151;">Position</h3>
                        </div>
                        <div class="vd-section-body">
                            <div style="margin-bottom: 1rem;">
                                <label style="display:block; font-weight:600; margin-bottom:0.25rem;">Whatsapp Position</label>
                                <select name="whatsapp_position">
                                    <option value="right" <?php selected(get_option('whatsapp_position'), 'right'); ?>>Right</option>
                                    <option value="left" <?php selected(get_option('whatsapp_position'), 'left'); ?>>Left</option>
                                </select>
                            </div>
                            <p>Posisi tombol dan tombol scroll-to-top akan mengikuti pilihan kanan/kiri.</p>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="vd-section">
                        <div class="vd-section-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; background-color: #f9fafb;">
                            <h3 style="margin:0; font-size:1.1rem; color:#374151;">Detail Whatsapp</h3>
                        </div>
                        <div class="vd-section-body">
                            <div style="margin-bottom: 1rem;">
                                <div id="wa-contacts-list" class="wa-contacts-list">
                                    <?php foreach ($contacts as $index => $contact) : ?>
                                        <?php $hide_remove = $index === 0 ? ' wa-remove-contact--hidden' : ''; ?>
                                        <div class="wa-contact-item">
                                            <div class="wa-contact-row">
                                                <div class="wa-contact-field">
                                                    <label>Nama Kontak</label>
                                                    <input class="regular-text" type="text" name="nomor_whatsapp_contacts[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($contact['name'] ?? ''); ?>" placeholder="Customer Service" />
                                                </div>
                                                <div class="wa-contact-field">
                                                    <label>Nomor Whatsapp</label>
                                                    <input class="regular-text" type="text" name="nomor_whatsapp_contacts[<?php echo esc_attr($index); ?>][number]" value="<?php echo esc_attr($contact['number'] ?? ''); ?>" placeholder="62xxxx atau 08xxxx" />
                                                    <small>Bisa diawali 62 atau 08</small>
                                                </div>
                                                <div class="wa-remove-contact<?php echo $hide_remove; ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" />
                                                        <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <hr class="wa-contact-separator">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="button button-secondary" id="add-wa-contact">Tambah Nomor</button>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="display:block; font-weight:600; margin-bottom:0.25rem;">Text Whatsapp</label>
                                <input class="regular-text" type="text" name="whatsapp_text" value="<?php echo esc_attr($whatsapp_text); ?>" />
                                <small>Akan ditampilkan jika nama kontak tidak diisi</small>
                            </div>
                            <div style="margin-bottom: 0;">
                                <label style="display:block; font-weight:600; margin-bottom:0.25rem;">Pesan Whatsapp</label>
                                <textarea class="large-text" name="whatsapp_message" rows="4" cols="40"><?php echo esc_textarea($whatsapp_message); ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php submit_button(); ?>
            </form>
            <?php if (! $is_style_page) : ?>
                <script>
                    (function() {
                        const list = document.getElementById('wa-contacts-list');
                        const addBtn = document.getElementById('add-wa-contact');
                        if (!list || !addBtn) return;

                        const rowTemplate = (index) => `
                        <div class="wa-contact-item">
                            <div class="wa-contact-row">
                                <div class="wa-contact-field">
                                    <label>Nama Kontak</label>
                                    <input class="regular-text" type="text" name="nomor_whatsapp_contacts[${index}][name]" placeholder="Customer Service" />
                                </div>
                                <div class="wa-contact-field">
                                    <label>Nomor Whatsapp</label>
                                    <input class="regular-text" type="text" name="nomor_whatsapp_contacts[${index}][number]" placeholder="62xxxx atau 08xxxx" />
                                    <small>Bisa diawali 62 atau 08</small>
                                </div>
                                <div class="wa-remove-contact">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                        <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                    </svg>
                                </div>
                            </div>
                            <hr class="wa-contact-separator">
                        </div>`;

                        addBtn.addEventListener('click', function() {
                            const index = list.querySelectorAll('.wa-contact-item').length;
                            list.insertAdjacentHTML('beforeend', rowTemplate(index));
                        });

                        list.addEventListener('click', function(e) {
                            const btn = e.target.closest('.wa-remove-contact');
                            if (!btn) return;
                            const item = btn.closest('.wa-contact-item');
                            if (item && list.querySelectorAll('.wa-contact-item').length > 1) {
                                item.remove();
                            }
                        });
                    })();
                </script>
            <?php endif; ?>
            <div class="vd-footer">
                <small>Powered by <a href="https://velocitydeveloper.com/" target="_blank">velocitydeveloper.com</a></small>
            </div>
        </div>
        <?php
    }

    public static function justg_footer_whatsapp()
    {
        $contacts               = self::normalize_whatsapp_contacts(get_option('nomor_whatsapp_contacts', []));
        $whatsapp_text          = get_option('whatsapp_text', 'Butuh Bantuan?');
        $whatsapp_message       = get_option('whatsapp_message', 'Halo..');
        $whatsapp_position      = get_option('whatsapp_position', 'right');
        $scroll_to_top_enable   = 'scroll-active scroll-' . $whatsapp_position;
        $position_class         = $whatsapp_position === 'left' ? 'left' : 'right';
        $encoded_message        = self::encode_whatsapp_message($whatsapp_message);

        $floating_whatsapp = get_option('floating_whatsapp', '1');
        if ($floating_whatsapp == '1' && !empty($contacts)) {
        ?>
            <div class="whatsapp-floating <?php echo $whatsapp_position . ' ' . $scroll_to_top_enable; ?>" style="position: relative;">
                <?php if (count($contacts) === 1) :
                    $contact = $contacts[0];
                    $wa_url = 'https://api.whatsapp.com/send?phone=' . rawurlencode($contact['number']) . '&text=' . $encoded_message;
                ?>
                    <a href="<?php echo esc_attr($wa_url); ?>" class="whatsapp-floating py-2 floating-button text-white d-flex align-items-center justify-content-center <?php echo $position_class; ?>" title="Whatsapp" target="_blank">
                        <span class="pt-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                                <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z" />
                            </svg>
                        </span>
                        <?php if ($whatsapp_text) : ?>
                            <span class="d-none d-md-inline-block"><?php echo esc_html($contact['name'] ?: $whatsapp_text); ?></span>
                        <?php endif; ?>
                    </a>
                <?php else : ?>
                    <button type="button" class="whatsapp-floating py-2 floating-button text-white d-flex align-items-center justify-content-center wa-multi-toggle <?php echo $position_class; ?>" aria-expanded="false" aria-controls="wa-multi-list">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-whatsapp align-middle me-0" viewBox="0 0 16 16">
                            <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z" />
                        </svg>
                        <?php if ($whatsapp_text) : ?>
                            <span class="wa-toggle-text d-none d-md-inline-block align-middle ms-1"><?php echo esc_html($whatsapp_text); ?></span>
                        <?php endif; ?>
                        <span class="wa-toggle-close m-0" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg m-0" viewBox="0 0 16 16">
                                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z" />
                            </svg>
                        </span>
                    </button>
                    <div id="wa-multi-list" class="wa-multi-list <?php echo $whatsapp_position === 'left' ? 'is-left' : 'is-right'; ?>">
                        <?php foreach ($contacts as $contact) :
                            $wa_url = 'https://api.whatsapp.com/send?phone=' . rawurlencode($contact['number']) . '&text=' . $encoded_message;
                            $contact_name = $contact['name'] ?: $contact['number'];
                        ?>
                            <a href="<?php echo esc_attr($wa_url); ?>" class="wa-multi-link" title="Whatsapp <?php echo esc_attr($contact_name); ?>" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-whatsapp align-middle" viewBox="0 0 16 16">
                                    <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z" />
                                </svg>
                                <span class="align-middle"><?php echo esc_html($contact_name); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php
        }
    }

    public static function add_floating_scrolltop()
    {
        $enable_scrolltop       = get_option('floating_scrollTop', '1');
        $whatsapp_position      = get_option('whatsapp_position', 'right');
        if ($enable_scrolltop == '1'):
        ?>
            <div class="scroll-to-top floating-button <?php echo $whatsapp_position; ?>" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-up" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708l6-6z" />
                </svg>
            </div>
<?php endif;
    }

    public function sanitize_whatsapp_contacts($value)
    {
        if ($value === null) {
            return get_option('nomor_whatsapp_contacts', []);
        }

        $normalized = self::normalize_whatsapp_contacts($value);

        // Sync legacy single number with first contact for backward compatibility.
        $first_number = $normalized[0]['number'] ?? '';
        update_option('nomor_whatsapp', $first_number);

        return $normalized;
    }

    public function sanitize_whatsapp_message($value)
    {
        if ($value === null) {
            return get_option('whatsapp_message', 'Halo..');
        }

        return self::normalize_whatsapp_message((string) $value);
    }

    private static function normalize_whatsapp_contacts($value)
    {
        if (!is_array($value)) {
            $value = $value ? [['name' => '', 'number' => $value]] : [];
        }

        $clean = [];
        foreach ($value as $contact) {
            if (!is_array($contact)) {
                continue;
            }
            $name = isset($contact['name']) ? sanitize_text_field($contact['name']) : '';
            $number = isset($contact['number']) ? preg_replace('/[^0-9]/', '', $contact['number']) : '';
            if (empty($number)) {
                continue;
            }
            if (substr($number, 0, 1) === '0') {
                $number = substr_replace($number, '62', 0, 1);
            }
            $clean[] = [
                'name'   => $name,
                'number' => $number,
            ];
        }

        return $clean;
    }

    public static function normalize_whatsapp_message($value)
    {
        if (!is_string($value)) {
            return '';
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $value);
        $normalized = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $normalized);
        $normalized = str_ireplace(['%0D%0A', '%0A', '%0D'], "\n", $normalized);

        return sanitize_textarea_field($normalized);
    }

    private static function encode_whatsapp_message($value)
    {
        $normalized = self::normalize_whatsapp_message((string) $value);
        return rawurlencode($normalized);
    }

    /**
     * One-time/ongoing migration to new option structure.
     * - nomor_whatsapp_contacts: array of contacts (new)
     * - nomor_whatsapp: legacy single string (kept in sync with first contact)
     */
    private function maybe_migrate_whatsapp_options()
    {
        $legacy_raw     = get_option('nomor_whatsapp', '');
        $contacts_raw   = get_option('nomor_whatsapp_contacts', null);
        $has_contacts   = is_array($contacts_raw) && !empty($contacts_raw);
        $legacy_is_str  = !is_array($legacy_raw) && !empty($legacy_raw);

        // Case 1: new option empty, legacy string exists -> seed contacts from legacy.
        if (!$has_contacts && $legacy_is_str) {
            $normalized = self::normalize_whatsapp_contacts($legacy_raw);
            update_option('nomor_whatsapp_contacts', $normalized);
            $first = $normalized[0]['number'] ?? '';
            update_option('nomor_whatsapp', $first);
            return;
        }

        // Case 2: contacts exist -> ensure normalized and legacy string synced to first contact.
        if ($has_contacts) {
            $normalized = self::normalize_whatsapp_contacts($contacts_raw);
            if ($normalized !== $contacts_raw) {
                update_option('nomor_whatsapp_contacts', $normalized);
            }
            $first = $normalized[0]['number'] ?? '';
            if ((string) $legacy_raw !== (string) $first) {
                update_option('nomor_whatsapp', $first);
            }
        }
    }
}

// Inisialisasi class Velocity_Addons_Floating_Whatsapp
$velocity_floating_whatsapp = new Velocity_Addons_Floating_Whatsapp();
