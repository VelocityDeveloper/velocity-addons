<?php
/**
 * Register Shortcode
 *
 * @link       https://velocitydeveloper.com
 * @since      1.0.0
 *
 * @package    Velocity_Addons
 * @subpackage Velocity_Addons/includes
 */
require_once dirname( __FILE__ ) . '/vdgallery-option-fields.php';

class Velocity_Addons_Shortcode {
    
    /**
     * Velocity_Addons_Shortcode constructor.
     */
    public function __construct() {
        add_shortcode('vd-breadcrumbs', [$this, 'vd_breadcrumbs']); //[vd-breadcrumbs]
        add_shortcode('velocity-sharepost', [$this, 'velocity_sharepost']); //[velocity-sharepost]
        add_shortcode( 'vdgallery', [$this, 'vdgallery_showgallery'] ); //[vdgallery id='']
        add_shortcode( 'vdgalleryslide', [$this, 'vdgallery_showslide'] ); //[vdgalleryslide id='']
    }

    public function vd_breadcrumbs() {
        ob_start();
        echo justg_breadcrumb();
        return ob_get_clean();
    }
    
    public function velocity_sharepost($atts) {
        ob_start();
        // Default values untuk atribut
        $atts = shortcode_atts([
            'title' => 'Share this post', // Judul
            'label_share'   => true,
            'platforms' => 'facebook,twitter,whatsapp,telegram,email', // Platform berbagi
        ], $atts, 'social_share'); // Nama shortcode
    
        // Ambil URL saat ini
        $current_url = esc_url(get_permalink());
        $current_title = esc_attr(get_the_title());
    
        // Pilih platform berbagi berdasarkan atribut
        $allowed_platforms = explode(',', $atts['platforms']);
        $all_links = [
            'facebook' => [
                'url' => "https://www.facebook.com/sharer/sharer.php?u={$current_url}",
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16"><path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951"/></svg>',
                'color' => '#4267B2',
            ],
            'twitter' => [
                'url' => "https://twitter.com/intent/tweet?url={$current_url}&text={$current_title}",
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16"><path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/></svg>',
                'color' => '#1DA1F2',
            ],
            'whatsapp' => [
                'url' => "https://api.whatsapp.com/send?text={$current_title} - {$current_url}",
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/></svg>',
                'color' => '#25D366',
            ],
            'telegram' => [
                'url' => "https://t.me/share/url?url={$current_url}&text={$current_title}",
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telegram" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.287 5.906q-1.168.486-4.666 2.01-.567.225-.595.442c-.03.243.275.339.69.47l.175.055c.408.133.958.288 1.243.294q.39.01.868-.32 3.269-2.206 3.374-2.23c.05-.012.12-.026.166.016s.042.12.037.141c-.03.129-1.227 1.241-1.846 1.817-.193.18-.33.307-.358.336a8 8 0 0 1-.188.186c-.38.366-.664.64.015 1.088.327.216.589.393.85.571.284.194.568.387.936.629q.14.092.27.187c.331.236.63.448.997.414.214-.02.435-.22.547-.82.265-1.417.786-4.486.906-5.751a1.4 1.4 0 0 0-.013-.315.34.34 0 0 0-.114-.217.53.53 0 0 0-.31-.093c-.3.005-.763.166-2.984 1.09"/></svg>',
                'color' => '#0088cc',
            ],
            'email' => [
                'url' => "mailto:?subject={$current_title}&body={$current_title} - {$current_url}",
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/></svg>',
                'color' => '#444',
            ],
        ];
    
        // Output
        echo '<div class="social-share-buttons">';
        echo '<p class="fw-bold mb-1">' . esc_html($atts['title']) . '</p>'; // Judul
        foreach ($all_links as $platform => $data) {
            if (in_array($platform, $allowed_platforms)) {
                $label = $atts['label_share']== 'true' ? '<span class="px-1">'.ucfirst($platform).'</span>' : '';
                echo "<a href='{$data['url']}' target='_blank' rel='noopener noreferrer' class='share-button share-{$platform}' style='color: white; background-color: {$data['color']}; padding: 10px; margin: 5px; border-radius: 5px; display: inline-flex; align-items: center; text-decoration: none;'>{$data['icon']} " .$label."</a>";
            }
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function vdgallery_showgallery( $atts ) {
        ob_start();   

        //generate node/id uniqe for shortcode 
        $idnode = 'vd'.uniqid();

        //set attribute shortcode
        $atribut = shortcode_atts( array(
            'id' => '',
        ), $atts );
        $id         = $atribut['id'];

        //set value by meta vdgaleri
        $vdgaleri           = get_post_meta( $id, 'vdgaleri', true );
        $media              = ( isset( $vdgaleri['media'] ) && is_array( $vdgaleri['media'] ) ) ? $vdgaleri['media'] : array();
        $option_values      = ( isset( $vdgaleri['option'] ) && is_array( $vdgaleri['option'] ) ) ? $vdgaleri['option'] : array();
        $global_options     = vdgallery_get_global_options();
        $gallery_use_global = ( isset( $vdgaleri['gallery_use_global_options'] ) && $vdgaleri['gallery_use_global_options'] );
        $gallery_values     = $gallery_use_global ? $global_options['gallery'] : wp_parse_args( $option_values, $global_options['gallery'] );
        $gallery_options    = vdgallery_sanitize_options( $gallery_values, vdgallery_get_gallery_option_fields() );
        $size               = $gallery_options['size'];
        $kolom              = $gallery_options['kolom'];
        $koloms             = 100/$kolom;
        $kolomres           = $gallery_options['kolomresponsif'];
        $kolomress          = 100/$kolomres;    
        $galericaption      = $gallery_options['galericaption'];
        $pagination         = $gallery_options['pagination'];
        $paginationitem     = $gallery_options['paginationitem'];
        $aspectratio        = $gallery_options['aspectratio'];
        $aspectratiocss     = str_replace( '/', ' / ', $aspectratio );
        $paginationitem     = max( 1, (int) $paginationitem );
        $pagination         = ( $pagination == true && count( $media ) > $paginationitem );
        $pagi               = array();

        ///show if have ID & meta vdgaleri
        if($id && $vdgaleri):
        ?>
            <div class="vdgallery-galleryshow vdgallery-galleryshow-<?php echo $idnode;?>" data-node="<?php echo $idnode;?>" data-id="<?php echo $id;?>">
                
                <?php if(!empty($media)): ?> 

                    <div class="vdgallery-kolom">
                    <?php $pagit = 1;?>
                    <?php $urut = 1;?>
                    <?php foreach($media as $idmedia): ?>

                        <?php 
                            $mediainfo = get_post($idmedia);
                            $caption   = $mediainfo ? $mediainfo->post_excerpt : '';
                        ?>

                        <div class="vdgallery-item vdgallery-item-<?php echo $idmedia;?>" data-id="<?php echo $idmedia;?>" data-urut="<?php echo $urut;?>" data-pagi="<?php echo $pagit;?>">
                            <div class="vdgallery-item-inside">
                            
                                <a class="vdgallery-item-link" href="<?php echo wp_get_attachment_image_src($idmedia,'full')[0]; ?>" title="<?php echo $caption;?>">
                                    <img class="vdgallery-item-image" src="<?php echo wp_get_attachment_image_src($idmedia,$size)[0]; ?>">
                                </a>      

                                <?php if(($galericaption!='tidak') && !empty($caption)): ?>
                                    <span class="vdgallery-item-caption vdgallery-item-caption-<?php echo $galericaption;?>">
                                        <?php echo $caption;?>
                                    </span>
                                <?php endif; ?>

                            </div>

                        </div>
                        <?php 
                        $pagi[$pagit][] = $idmedia;
                        if($pagination==true && $urut==$paginationitem) {
                            $urut = 0;
                            $pagit++;
                        }
                        $urut++;
                        ?>
                    <?php endforeach;?>
                    </div>
                    
                    <?php
                    //pagination if true
                    if($pagination==true) { 
                        echo '<div class="vdgallery-pagination" data-node="'.$idnode.'">';
                        foreach ($pagi as $key => $value) {
                            echo '<div class="vdgallery-pagi-btn" data-pagi="'.$key.'" data-node="'.$idnode.'">';
                                echo '<span>'.$key.'</span>';
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    ?>

                <?php endif;?>

                <style>
                    .vdgallery-galleryshow-<?php echo $idnode;?> {
                        position: relative;
                    }
                    .vdgallery-galleryshow-<?php echo $idnode;?> .vdgallery-item {
                        -webkit-flex: 0 0 <?php echo $kolomress;?>%;
                        -ms-flex: 0 0 <?php echo $kolomress;?>%;
                        flex: 0 0 <?php echo $kolomress;?>%;
                        max-width: <?php echo $kolomress;?>%;
                    }
                    <?php if($aspectratio !== 'none'): ?>
                    .vdgallery-galleryshow-<?php echo $idnode;?> .vdgallery-item-link {
                        aspect-ratio: <?php echo $aspectratiocss;?>;
                        display: block;
                        overflow: hidden;
                    }
                    .vdgallery-galleryshow-<?php echo $idnode;?> .vdgallery-item-image {
                        height: 100%;
                        object-fit: cover;
                    }
                    <?php endif; ?>
                    @media (min-width: 768px) {
                        .vdgallery-galleryshow-<?php echo $idnode;?> .vdgallery-item {
                            -webkit-flex: 0 0 <?php echo $koloms;?>%;
                            -ms-flex: 0 0 <?php echo $koloms;?>%;
                            flex: 0 0 <?php echo $koloms;?>%;
                            max-width: <?php echo $koloms;?>%;
                        }
                    }
                </style>
                
            </div>
        <?php
        endif; //endif have ID & meta vdgaleri

        return ob_get_clean();
    }

    public function vdgallery_showslide( $atts ) {
        ob_start();   

        //generate node/id uniqe for shortcode 
        $idnode = 'vd'.uniqid();

        //set attribute shortcode
        $atribut = shortcode_atts( array(
            'id' => '',
        ), $atts );
        $id         = $atribut['id'];

        //set value by meta vdgaleri
        $vdgaleri       = get_post_meta( $id, 'vdgaleri', true );
        $media          = ( isset( $vdgaleri['media'] ) && is_array( $vdgaleri['media'] ) ) ? $vdgaleri['media'] : array();
        $option_values  = ( isset( $vdgaleri['option'] ) && is_array( $vdgaleri['option'] ) ) ? $vdgaleri['option'] : array();
        $global_options = vdgallery_get_global_options();
        $slide_use_global = ( isset( $vdgaleri['slideshow_use_global_options'] ) && $vdgaleri['slideshow_use_global_options'] );
        $slide_values   = $slide_use_global ? $global_options['slideshow'] : wp_parse_args( $option_values, $global_options['slideshow'] );
        $slide_options  = vdgallery_sanitize_options( $slide_values, vdgallery_get_slideshow_option_fields() );
        $size           = $slide_options['slidesize'];
        $perslide       = $slide_options['perslide'];
        $perslideres    = $slide_options['persliderespon'];
        $aspectratio    = $slide_options['slideaspectratio'];
        $aspectratiocss = str_replace( '/', ' / ', $aspectratio );
        $slidecaption   = $slide_options['slidecaption'];

        //flickity opt
        $flickity   = [
            'contain'           => true,
            'cellAlign'         => 'left',
            'adaptiveHeight'    => false,
            'prevNextButtons'   => (bool)$slide_options['navbtn'],
            'pageDots'          => (bool)$slide_options['navdots'],
            'autoPlay'          => (bool)$slide_options['autoplay'],
            'wrapAround'        => (bool)$slide_options['infinite'],
        ];
        $flickity   = json_encode($flickity);

        ///show if have ID & meta vdgaleri
        if($id && $vdgaleri):
        ?>
        <div class="vdgallery-slideshow overflow-hidden vdgallery-slideshow-<?php echo $idnode;?>" data-node="<?php echo $idnode;?>" data-id="<?php echo $id;?>">
            
            <?php if(!empty($media)): ?>

                <div class="vdgallery-slide-box" data-node="<?php echo $idnode;?>">
                    <div class="vdgallery-slide" data-flickity='<?php echo $flickity;?>'>
                        <?php foreach($media as $idmedia): ?>
                            <?php
                                $mediainfo = get_post($idmedia);
                                $caption   = $mediainfo ? $mediainfo->post_excerpt : '';
                            ?>
                            <div class="vdgallery-item vdgallery-item-<?php echo $idmedia;?>" data-id="<?php echo $idmedia;?>">
                                <div class="vdgallery-item-inside">
                                    <a class="vdgallery-item-link" target="_blank" href="<?php echo wp_get_attachment_image_src($idmedia,'full')[0]; ?>" title="<?php echo $caption;?>">  
                                        <img class="vdgallery-item-image" src="<?php echo wp_get_attachment_image_src($idmedia,$size)[0]; ?>">
                                    </a>
                                    <?php if(($slidecaption!='tidak') && !empty($caption)): ?>
                                        <span class="vdgallery-item-caption vdgallery-item-caption-<?php echo $slidecaption;?>">
                                            <?php echo $caption;?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach;?>
                    </div>
                </div>
                
            <?php endif;?>

            <style>
                .vdgallery-slideshow-<?php echo $idnode;?> .vdgallery-item {
                    width: <?php echo 100/$perslideres;?>%;
                }
                .vdgallery-slideshow-<?php echo $idnode;?> .vdgallery-item-inside {
                    position: relative;
                    overflow: hidden;
                }
                .vdgallery-slideshow-<?php echo $idnode;?> .vdgallery-item-link {
                    display: block;
                }
                <?php if($aspectratio !== 'none'): ?>
                .vdgallery-slideshow-<?php echo $idnode;?> .vdgallery-item-link {
                    aspect-ratio: <?php echo $aspectratiocss;?>;
                    overflow: hidden;
                }
                .vdgallery-slideshow-<?php echo $idnode;?> .vdgallery-item-image {
                    height: 100%;
                    object-fit: cover;
                }
                <?php endif; ?>
                @media (min-width: 768px) {
                    .vdgallery-slideshow-<?php echo $idnode;?> .vdgallery-item {
                        width: <?php echo 100/$perslide;?>%;
                    }
                }
            </style>

        </div>
        <?php
        endif; //endif have ID & meta vdgaleri

        return ob_get_clean();
    }
}

// Inisialisasi class Velocity_Addons_Shortcode
 $velocity_shortcode = new Velocity_Addons_Shortcode();
