<?php
class qa_html_theme_layer extends qa_html_theme_base {
	var $theme_directory;
	var $theme_url;
	function __construct($template, $content, $rooturl, $request)
	{
		global $qa_layers;
		$this->theme_directory = $qa_layers['Theme Options']['directory'];
		$this->theme_url = $qa_layers['Theme Options']['urltoroot'];
		qa_html_theme_base::qa_html_theme_base($template, $content, $rooturl, $request);
	}
	
	function option_default($option)
	{

	}
	function q_list($q_list)
	{
		if (count(@$q_list['qs']) && qa_opt('qat_excerpt_on')) { // first check it is not an empty list and the feature is turned on
		//	Collect the question ids of all items in the question list (so we can do this in one DB query)
			$postids=array();
			foreach ($q_list['qs'] as $question)
				if (isset($question['raw']['postid']))
					$postids[]=$question['raw']['postid'];
			if (count($postids)) {
			//	Retrieve the content for these questions from the database and put into an array
				$result=qa_db_query_sub('SELECT postid, content, format FROM ^posts WHERE postid IN (#)', $postids);
				$postinfo=qa_db_read_all_assoc($result, 'postid');
			//	Get the regular expression fragment to use for blocked words and the maximum length of content to show
				$blockwordspreg=qa_get_block_words_preg();
				$maxlength=qa_opt('qat_excerpt_max_len');
			//	Now add the popup to the title for each question
				foreach ($q_list['qs'] as $index => $question) {
                    $thispost=@$postinfo[$question['raw']['postid']];
                    if (isset($thispost)) {
                        $text=qa_viewer_text($thispost['content'], $thispost['format'], array('blockwordspreg' => $blockwordspreg));
                        $text=qa_shorten_string_line($text, $maxlength);
                        $q_list['qs'][$index]['content']='<SPAN >'.qa_html($text).'</SPAN>';
                    }
                } 
			}
		}
		qa_html_theme_base::q_list($q_list); // call back through to the default function
	}
	
	function nav_list($navigation, $class, $level=null)
	{
		if($this->template=='admin') {
			if ($class == 'nav-sub')
				$navigation['theme_options'] = array(
					  'label' => 'Theme Options',
					  'url' => qa_path_html('admin/theme_options'),
				);
			if($this->request == 'admin/theme_options') {
				unset($navigation['special']);
				$newnav = qa_admin_sub_navigation();
				$navigation = array_merge($newnav, $navigation);
				$navigation['theme_options']['selected'] = true;
			}
		}
		if(count($navigation) > 1 ) qa_html_theme_base::nav_list($navigation, $class, $level=null);
	}

	function head_css() {
		parent::head_css();

		if (qa_opt('qat_custom_css')!='NO Custom Style')
			$this->output('<link href="' . $this->theme_url . 'styles/' . qa_opt('qat_custom_css') . '.css" type="text/css" rel="stylesheet"></link>');
		if ($this->template=='theme_options'){
			$this->output('<style type="text/css">.qa-option-header{font-size: 115%;}</style>');
		}
		$this->output('<style type="text/css">');
		echo qa_opt('qat_custom_style');
		$this->output('</style>');
	}
	function body_script()
	{
		if ($this->template=='theme_options'){
			$this->output('<link rel="stylesheet" media="screen" type="text/css" href="' . $this->theme_url .'includes/colorpicker.css" />');
			$this->output('<script src="' . $this->theme_url .'includes/colorpicker.min.js"></script>');
			$this->output(
				'<script type="text/javascript">
				$(document).ready(function() {
					$(\'#qat_bg_color_field\').ColorPicker({
						onShow: function (colpkr) {
							$(colpkr).fadeIn(500);
							return false;
						},
						onHide: function (colpkr) {
							$(colpkr).fadeOut(500);
							return false;
						},
						onChange: function (hsb, hex, rgb) {
							$(\'#qat_bg_color_field\').css(\'backgroundColor\', \'#\' + hex);
							$(\'#qat_bg_color_field\').val(\'#\' + hex);
							
						}
					}); 
				});
			</script>'
			);
		}
		if (qa_opt('qat_enable_loadingbar'))
			$this->output(
				'<script type="text/javascript">
				$(\'body\').show();
				$(\'.version\').text(NProgress.version);
				NProgress.start();
				setTimeout(function() { NProgress.done(); $(\'.fade\').removeClass(\'out\'); }, 0); 
				</script>'
			);
		$this->output(
			'<script type="text/javascript">
				if (typeof qa_wysiwyg_editor_config == "object")
					qa_wysiwyg_editor_config.skin="kama";
			</script>'
		);
		qa_html_theme_base::body_script();
	}

	function nav_user_search(){
		qa_html_theme_base::nav_user_search();
		if (qa_opt('qat_askbox')) 
			$this->output_askbox();
	}
	//
	// external functions
	//
	function output_askbox()
	{
		?>
			<DIV CLASS="qa-support-ask-box">
				<FORM METHOD="POST" ACTION="<?php echo qa_path_html('ask'); ?>">
					<INPUT NAME="title" TYPE="text" CLASS="qa-form-tall-ask" placeholder="What do you have in mind?">
					<INPUT TYPE="hidden" NAME="doask1" VALUE="1">
				</FORM>
			</DIV>
		<?php
	}
	
	function doctype(){
		// CSS Customizations will need this variables
		global $p_path, $s_path,$p_url,$s_url;
		global $qa_request;
		if ( ($qa_request == 'admin/theme_options') and (qa_get_logged_in_level()>=QA_USER_LEVEL_ADMIN) ) {
			//$this->theme_url , $this->theme_directory
			$p_path = $this->theme_directory . 'patterns';
			$s_path = $this->theme_directory . 'styles';
			$p_url = $this->theme_url . 'patterns';
			$s_url = $this->theme_url . 'styles';

			$this->template="theme_options";
			$this->content['navigation']['sub'] = qa_admin_sub_navigation();
			$this->content['navigation']['sub']['theme_options'] = array(
				  'label' => 'Theme Options',
				  'url' => qa_path_html('admin/theme_options'),
				  'selected' => 'selected',
			);
			$this->content['site_title']="Options";
			$this->content['error']="";
			$this->content['suggest_next']="";
			$this->content['title']="Support Theme";
				
				
			$saved=false;
			//Main Navigation Menu's Position
				$main_menu_position=array(
					'On left side of main area',
					'Above main area'
				);
			// CSS Compression options
				$compression = array('Normal CSS Styling','Compressed CSS','GZip Compressed CSS');
			// List of BackGrounds
				$bg_images=array();
				$files = scandir($p_path, 1);
				$bg_images[]="Default Background";
				$bg_images[]="NO Background";
				foreach ($files as $file) 
					if (!((empty($file)) or($file=='.') or ($file=='..')))
						$bg_images[] = preg_replace("/\\.[^.]*$/", "", $file);
			// List of styles
				$styles=array();
				$files = scandir($s_path, 1);
				$styles[]="NO Custom Style";
				foreach ($files as $file) 
					if (!((empty($file)) or($file=='.') or ($file=='..')))
						$styles[] = preg_replace("/\\.[^.]*$/", "", $file);
			// List Of Font Stacks
				$font_name=array(
					'Default Theme Font',
					'Times New Roman based serif',
					'Modern Georgia+Times based serif',
					'Traditional Garamond-based serif',
					'The Helvetica/Arial-based sans serif',
					'The Verdana-based sans serif',
					'The Trebuchet-based sans serif',
					'The heavier "Impact" sans serif',
					'The monospace',
					'Monospace Typewriter',
					'Display Serifs',
					'Geometric ',
					'Neo Grotesque',
					'Readable Verdana+Tahoma',
					'Small Readable Corbel+Lucida',
					);
				$font_style=array(
					'', // default fonts
					'Cambria, "Hoefler Text", Utopia, "Liberation Serif", "Nimbus Roman No9 L Regular", Times, "Times New Roman", serif',
					'Didot,"Bodoni MT", "Century Schoolbook", "Niagara Solid", Utopia, Georgia, Times, "Times New Roman", serif',
					'"Palatino Linotype", Palatino, Palladio, "URW Palladio L", "Book Antiqua", Baskerville, "Bookman Old Style", "Bitstream Charter", "Nimbus Roman No9 L", Garamond, "Apple Garamond", "ITC Garamond Narrow", "New Century Schoolbook", "Century Schoolbook", "Century Schoolbook L", Georgia, serif',
					'Frutiger, "Frutiger Linotype", Univers, Calibri, "Gill Sans", "Gill Sans MT", "Myriad Pro", Myriad, "DejaVu Sans Condensed", "Liberation Sans", "Nimbus Sans L", Tahoma, Geneva, "Helvetica Neue", Helvetica, Arial, sans-serif',
					'Corbel, "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", "DejaVu Sans", "Bitstream Vera Sans", "Liberation Sans", Verdana, "Verdana Ref", sans-serif',
					'"Segoe UI", Candara, "Bitstream Vera Sans", "DejaVu Sans", "Bitstream Vera Sans", "Trebuchet MS", Verdana, "Verdana Ref", sans-serif',
					'Impact, Haettenschweiler, "Franklin Gothic Bold", Charcoal, "Helvetica Inserat", "Bitstream Vera Sans Bold", "Arial Black", sans-serif',
					'Consolas, "Bitstream Vera Sans Mono", "Andale Mono", Monaco, "DejaVu Sans Mono", "Lucida Console',
					'"Courier New", Courier, "Lucida Sans Typewriter", "Lucida Typewriter", monospace',
					'Perpetua, Baskerville, "Big Caslon", "Palatino Linotype", Palatino, "URW Palladio L", "Nimbus Roman No9 L", serif',
					'"Century Gothic", "Tw Cen MT", Futura, "URW Gothic L", Arial, sans-serif',
					'Corbel, Arial, Helvetica, "Nimbus Sans L", "Liberation Sans", sans-serif',
					'Verdana, Tahoma, "Trebuchet MS", "DejuVu Sans", "Bitstream Vera Sans", sans-serif',
					'Corbel, "Lucida Sans Unicode", "Lucida Grade", "Bitstream Vera Sans", "Luxi Serif", Verdana, sans-serif',
				);
			// Save Settings
			if (qa_clicked('qat_save_button')) {
				qa_opt('qat_excerpt_on', (int)qa_post_text('qat_excerpt_on_field'));
				qa_opt('qat_excerpt_max_len', (int)qa_post_text('qat_excerpt_max_len_field'));
				qa_opt('qat_bg_color_on', (int)qa_post_text('qat_bg_color_on_field'));
				qa_opt('qat_bg_color', qa_post_text('qat_bg_color_field'));
				qa_opt('qat_bg_image', $bg_images[(int)qa_post_text('qat_bg_image_field')]);
				qa_opt('qat_bg_image_index', (int)qa_post_text('qat_bg_image_field'));

				qa_opt('qat_b_font', $font_style[(int)qa_post_text('qat_b_font_field')]);
				qa_opt('qat_b_font_index', (int)qa_post_text('qat_b_font_field'));
				qa_opt('qat_main_nav_font', $font_style[(int)qa_post_text('qat_main_nav_font_field')]);
				qa_opt('qat_main_nav_font_index', (int)qa_post_text('qat_main_nav_font_field'));
				qa_opt('qat_user_nav_font', $font_style[(int)qa_post_text('qat_user_nav_font_field')]);
				qa_opt('qat_user_nav_font_index', (int)qa_post_text('qat_user_nav_font_field'));
				qa_opt('qat_sub_nav_font', $font_style[(int)qa_post_text('qat_sub_nav_font_field')]);
				qa_opt('qat_sub_nav_font_index', (int)qa_post_text('qat_sub_nav_font_field'));
				qa_opt('qat_q_list_title_font', $font_style[(int)qa_post_text('qat_q_list_title_font_field')]);
				qa_opt('qat_q_list_title_font_index', (int)qa_post_text('qat_q_list_title_font_field'));
				qa_opt('qat_q_list_excerp_font', $font_style[(int)qa_post_text('qat_q_list_excerp_font_field')]);
				qa_opt('qat_q_list_excerp_font_index', (int)qa_post_text('qat_q_list_excerp_font_field'));

				qa_opt('qat_q_title_font', $font_style[(int)qa_post_text('qat_q_title_font_field')]);
				qa_opt('qat_q_title_font_index', (int)qa_post_text('qat_q_title_font_field'));
				qa_opt('qat_q_content_font', $font_style[(int)qa_post_text('qat_q_content_font_field')]);
				qa_opt('qat_q_content_font_index', (int)qa_post_text('qat_q_content_font_field'));

				qa_opt('qat_a_content_font', $font_style[(int)qa_post_text('qat_a_content_font_field')]);
				qa_opt('qat_a_content_font_index', (int)qa_post_text('qat_a_content_font_field'));
				qa_opt('qat_c_content_font', $font_style[(int)qa_post_text('qat_c_content_font_field')]);
				qa_opt('qat_c_content_font_index', (int)qa_post_text('qat_c_content_font_field'));

				qa_opt('qat_s_bar_font', $font_style[(int)qa_post_text('qat_s_bar_font_field')]);
				qa_opt('qat_s_bar_font_index', (int)qa_post_text('qat_s_bar_font_field'));
				qa_opt('qat_sb_bar_font', $font_style[(int)qa_post_text('qat_sb_bar_font_field')]);
				qa_opt('qat_sb_bar_font_index', (int)qa_post_text('qat_sb_bar_font_field'));

				qa_opt('qat_fs_b', (int)qa_post_text('qat_fs_b_field'));
				qa_opt('qat_fs_main_nav', (int)qa_post_text('qat_fs_main_nav_field'));
				qa_opt('qat_fs_sub_nav', (int)qa_post_text('qat_fs_sub_nav_field'));
				qa_opt('qat_fs_user_nav', (int)qa_post_text('qat_fs_user_nav_field'));
				qa_opt('qat_fs_q_list_title', (int)qa_post_text('qat_fs_q_list_title_field'));
				qa_opt('qat_fs_q_list_excerp', (int)qa_post_text('qat_fs_q_list_excerp_field'));
				qa_opt('qat_fs_q_title', (int)qa_post_text('qat_fs_q_title_field'));
				qa_opt('qat_fs_q_content', (int)qa_post_text('qat_fs_q_content_field'));
				qa_opt('qat_fs_a_content', (int)qa_post_text('qat_fs_a_content_field'));
				qa_opt('qat_fs_c_content', (int)qa_post_text('qat_fs_c_content_field'));
				qa_opt('qat_fs_s_bar', (int)qa_post_text('qat_fs_s_bar_field'));
				qa_opt('qat_fs_sb_bar', (int)qa_post_text('qat_fs_sb_bar_field'));
				
				qa_opt('qat_askbox', (int)qa_post_text('qat_askbox_field'));
				qa_opt('qat_sidebar_question', (int)qa_post_text('qat_sidebar_question_field'));
				qa_opt('qat_sidebar_other', (int)qa_post_text('qat_sidebar_other_field'));
				qa_opt('qat_compression', (int)qa_post_text('qat_compression_field'));
				qa_opt('qat_custom_css', $styles[(int)qa_post_text('qat_custom_css_field')]);
				qa_opt('qat_custom_css_index', (int)qa_post_text('qat_custom_css_field'));
				qa_opt('main_menu_position', (int)qa_post_text('main_menu_position'));

				require_once($this->theme_directory . '/qa-styles.php'); // Generate customized CSS styling
				$saved=true;
			}

			// Option Form
			$options= array(
				'ok' => $saved ? 'Support Theme\'s settings saved' : null,
				'tags' => 'METHOD="POST" ACTION="'.qa_path_html(qa_request()).'"',
				'style' => 'wide', // wide , tall
				'fields' => array(
					array(
						'type' => 'static',
						'label' => '<span class="qa-option-header"> Excerp in question list </span>',
					),
					array(
						'label' => 'Show excerpt in question lists',
						'type' => 'checkbox',
						'value' => qa_opt('qat_excerpt_on'),
						'tags' => 'NAME="qat_excerpt_on_field" ID="qat_excerpt_on_field"',
					),
					array(
						'label' => 'Maximum length of preview:',
						'suffix' => 'characters',
						'type' => 'number',
						'value' => (int)qa_opt('qat_excerpt_max_len'),
						'tags' => 'NAME="qat_excerpt_max_len_field" ID="qat_excerpt_max_len_field"',
					),
					array(
						'type' => 'blank',
					),
					array(
						'type' => 'static',
						'label' => '<span class="qa-option-header"> Background Customization </span>',
					),
					array(
						'label' => 'Customize Background Color',
						'type' => 'checkbox',
						'tags' => 'NAME="qat_bg_color_on_field" ID="qat_bg_color_on_field"',
						'value' => qa_opt('qat_bg_color_on'),
						),
					array(
						'label' => 'Background Color',
						'tags' => 'NAME="qat_bg_color_field" ID="qat_bg_color_field"',
						'value' => qa_opt('qat_bg_color'),
						),
					array(
						'label' => 'Background Image',
						'tags' => 'NAME="qat_bg_image_field" ID="qat_bg_image_field"',
						'type' => 'select',
						'options' => @$bg_images,
						'value' => @$bg_images[qa_opt('qat_bg_image_index')],
					),
					array(
						'type' => 'blank',
					),
					array(
						'type' => 'static',
						'label' => '<span class="qa-option-header"> Typography </span>',
					),
					array(
						'label' => 'Body Font',
						'tags' => 'NAME="qat_b_font_field" ID="qat_b_font_field"',
						'type' => 'select',
						'options' => @$font_name,
						'value' => @$font_name[qa_opt('qat_b_font_index')],
					),
					array(
						'label' => 'Body Font Size',
						'tags' => 'NAME="qat_fs_b_field" ID="qat_fs_b_field"',
						'type' => 'number',
						'value' => (int)qa_opt('qat_fs_b'),
						'suffix' => '<strong>px</strong> - <small>Zero(0) for default value</small>',
					),
					array(
						'type' => 'custom',
						'html' => '',
					),
					array(
						'label' => 'Main Navigation Font',
						'tags' => 'NAME="qat_main_nav_font_field" ID="qat_main_nav_font_field"',
						'type' => 'select',
						'options' => @$font_name,
						'value' => @$font_name[qa_opt('qat_main_nav_font_index')],
					),
					array(
						'label' => 'Main Navigation Font Size',
						'tags' => 'NAME="qat_fs_main_nav_field" ID="qat_fs_main_nav_field"',
						'type' => 'number',
						'value' => (int)qa_opt('qat_fs_main_nav'),
						'suffix' => '<strong>px</strong> - <small>Zero(0) for default value</small>',
					),
					array(
						'label' => 'Sub Navigation Font',
						'tags' => 'NAME="qat_sub_nav_font_field" ID="qat_main_sub_font_field"',
						'type' => 'select',
						'options' => @$font_name,
						'value' => @$font_name[qa_opt('qat_sub_nav_font_index')],
					),
					array(
						'label' => 'Sub Navigation Font Size',
						'tags' => 'NAME="qat_fs_sub_nav_field" ID="qat_fs_sub_nav_field"',
						'type' => 'number',
						'value' => (int)qa_opt('qat_fs_sub_nav'),
						'suffix' => '<strong>px</strong> - <small>Zero(0) for default value</small>',
					),
					array(
						'label' => 'User Navigation Font',
						'tags' => 'NAME="qat_user_nav_font_field" ID="qat_user_nav_font_field"',
						'type' => 'select',
						'options' => @$font_name,
						'value' => @$font_name[qa_opt('qat_user_nav_font_index')],
					),
					array(
						'label' => 'User Navigation Font Size',
						'tags' => 'NAME="qat_fs_user_nav_field" ID="qat_fs_user_nav_field"',
						'type' => 'number',
						'value' => (int)qa_opt('qat_fs_user_nav'),
						'suffix' => '<strong>px</strong> - <small>Zero(0) for default value</small>',
					),
					array(
						'type' => 'custom',
						'html' => '',
					),
					array(
						'label' => 'Question Title in lists',
						'tags' => 'NAME="qat_q_list_title_font_field" ID="qat_q_list_title_font_field"',
						'type' => 'select',
						'options' => @$font_name,
						'value' => @$font_name[qa_opt('qat_q_list_title_font_index')],
					),
					array(
						'label' => 'Question Title Font Size',
						'tags' => 'NAME="qat_fs_q_list_title_field" ID="qat_fs_q_list_title_field"',
						'type' => 'number',
						'value' => (int)qa_opt('qat_fs_q_list_title'),
						'suffix' => '<strong>px</strong> - <small>Zero(0) for default value</small>',
					),
					array(
						'label' => 'Question Excerpt in lists',
						'tags' => 'NAME="qat_q_list_excerp_font_field" ID="qat_q_list_excerp_font_field"',
						'type' => 'select',
						'options' => @$font_name,
						'value' => @$font_name[qa_opt('qat_q_list_excerp_font_index')],
					),
					array(
						'label' => 'Question Excerpt Font Size',
						'tags' => 'NAME="qat_fs_q_list_excerp_field" ID="qat_fs_q_list_field"',
						'type' => 'number',
						'value' => (int)qa_opt('qat_fs_q_list_excerp'),
						'suffix' => '<strong>px</strong> - <small>Zero(0) for default value</small>',
					),
					array(
						'type' => 'custom',
						'html' => '',
					),
					array(
						'label' => 'Question Title Font',
						'tags' => 'NAME="qat_q_title_font_field" ID="qat_q_title_font_field"',
						'type' => 'select',
						'options' => @$font_name,
						'value' => @$font_name[qa_opt('qat_q_title_font_index')],
					),
					array(
						'label' => 'Question Title Font Size',
						'tags' => 'NAME="qat_fs_q_title_field" ID="qat_fs_q_title_field"',
						'type' => 'number',
						'value' => (int)qa_opt('qat_fs_q_title'),
						'suffix' => '<strong>px</strong> - <small>Zero(0) for default value</small>',
					),
					array(
						'label' => 'Question Content Form',
						'tags' => 'NAME="qat_q_content_font_field" ID="qat_q_content_font_field"',
						'type' => 'select',
						'options' => @$font_name,
						'value' => @$font_name[qa_opt('qat_q_content_font_index')],
					),
					array(
						'label' => 'Question Content Font Size',
						'tags' => 'NAME="qat_fs_q_content_field" ID="qat_fs_q_content_field"',
						'type' => 'number',
						'value' => (int)qa_opt('qat_fs_q_content'),
						'suffix' => '<strong>px</strong> - <small>Zero(0) for default value</small>',
					),
					array(
						'label' => 'Answer Content Font',
						'tags' => 'NAME="qat_a_content_font_field" ID="qat_a_content_font_field"',
						'type' => 'select',
						'options' => @$font_name,
						'value' => @$font_name[qa_opt('qat_a_content_font_index')],
					),
					array(
						'label' => 'Answer Content Font Size',
						'tags' => 'NAME="qat_fs_a_content_field" ID="qat_fs_a_content_field"',
						'type' => 'number',
						'value' => (int)qa_opt('qat_fs_a_content'),
						'suffix' => '<strong>px</strong> - <small>Zero(0) for default value</small>',
					),
					array(
						'label' => 'Comment Content Form',
						'tags' => 'NAME="qat_c_content_font_field" ID="qat_c_content_font_field"',
						'type' => 'select',
						'options' => @$font_name,
						'value' => @$font_name[qa_opt('qat_c_content_font_index')],
					),
					array(
						'label' => 'Comment Content Font Size',
						'tags' => 'NAME="qat_fs_c_content_field" ID="qat_fs_c_content_field"',
						'type' => 'number',
						'value' => (int)qa_opt('qat_fs_c_content'),
						'suffix' => '<strong>px</strong> - <small>Zero(0) for default value</small>',
					),
					array(
						'type' => 'custom',
						'html' => '',
					),
					array(
						'label' => 'Side Panel(sidebar) Font',
						'tags' => 'NAME="qat_s_bar_font_field" ID="qat_s_bar_font_field"',
						'type' => 'select',
						'options' => @$font_name,
						'value' => @$font_name[qa_opt('qat_s_bar_font_index')],
					),
					array(
						'label' => 'Side Panel(sidebar) Font Size',
						'tags' => 'NAME="qat_fs_s_bar_field" ID="qat_fs_s_bar_field"',
						'type' => 'number',
						'value' => (int)qa_opt('qat_fs_s_bar'),
						'suffix' => '<strong>px</strong> - <small>Zero(0) for default value</small>',
					),
					array(
						'label' => 'Sidebar Box on every page',
						'tags' => 'NAME="qat_sb_bar_font_field" ID="qat_sb_bar_font_field"',
						'type' => 'select',
						'options' => @$font_name,
						'value' => @$font_name[qa_opt('qat_sb_bar_font_index')],
					),
					array(
						'label' => 'Sidebar Box Font Size',
						'tags' => 'NAME="qat_fs_sb_bar_field" ID="qat_fs_sb_bar_field"',
						'type' => 'number',
						'value' => (int)qa_opt('qat_fs_sb_bar'),
						'suffix' => '<strong>px</strong> - <small>Zero(0) for default value</small>',
					),
					array(
						'type' => 'blank',
					),
					array(
						'type' => 'static',
						'label' => '<span class="qa-option-header"> Layout </span>',
					),
					array(
						'label' => 'Enable "Ask Box" at header.',
						'type' => 'checkbox',
						'value' => qa_opt('qat_askbox'),
						'tags' => 'NAME="qat_askbox_field" ID="qat_askbox_field"',
					),
					array(
						'label' => 'Show Sidebar in each questions page',
						'type' => 'checkbox',
						'value' => qa_opt('qat_sidebar_question'),
						'tags' => 'NAME="qat_sidebar_question_field" ID="qat_sidebar_question_field"',
					),
					array(
						'label' => 'Show Sidebar in other pages',
						'type' => 'checkbox',
						'value' => qa_opt('qat_sidebar_other'),
						'tags' => 'NAME="qat_sidebar_other_field" ID="qat_sidebar_other_field"',
					),
					array(
						'label' => 'Main Menu Position',
						'tags' => 'NAME="main_menu_position"',
						'type' => 'select',
						'options' => $main_menu_position,
						'value' => @$main_menu_position[qa_opt('main_menu_position')],
					),
					array(
						'type' => 'blank',
					),
					array(
						'type' => 'static',
						'label' => '<span class="qa-option-header"> CSS Stlye Files </span>',
					),
					array(
						'label' => 'CSS Style\'s Compression',
						'tags' => 'NAME="qat_compression_field" ID="qat_compression_field"',
						'type' => 'select',
						'options' => $compression,
						'value' => @$compression[qa_opt('qat_compression')],
					),
					array(
						'label' => 'CSS Styles',
						'tags' => 'NAME="qat_custom_css_field" ID="qat_custom_css_field"',
						'type' => 'select',
						'options' => @$styles,
						'value' => @$styles[qa_opt('qat_custom_css_index')],
					),
				),
				//But it's against the spirit of the GPL and open source
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'NAME="qat_save_button"',
					),
				),
			);
			$this->content['form']=$options;
		}
		qa_html_theme_base::doctype();
	}
}
/*
	Omit PHP closing tag to help avoid accidental output
*/
