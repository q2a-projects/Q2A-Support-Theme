<?php

$theme_dir = dirname( __FILE__ ) . '/';
$theme_url = qa_opt('site_url') . 'qa-theme/' . qa_get_site_theme() . '/';
qa_register_layer('/qa-admin-options.php', 'Theme Options', $theme_dir , $theme_url );


	class qa_html_theme extends qa_html_theme_base
	{	
		function head_css()
		{
			if (qa_opt('qat_compression')==2) //Gzip
				$this->output('<LINK REL="stylesheet" TYPE="text/css" HREF="'.$this->rooturl.'qa-styles-gzip.php'.'"/>');
			elseif (qa_opt('qat_compression')==1) //CSS Compression
				$this->output('<LINK REL="stylesheet" TYPE="text/css" HREF="'.$this->rooturl.'qa-styles-commpressed.css'.'"/>');
			else // Normal CSS load
				$this->output('<LINK REL="stylesheet" TYPE="text/css" HREF="'.$this->rooturl.$this->css_name().'"/>');
			
			if (isset($this->content['css_src']))
				foreach ($this->content['css_src'] as $css_src)
					$this->output('<LINK REL="stylesheet" TYPE="text/css" HREF="'.$css_src.'"/>');
					
			if (!empty($this->content['notices']))
				$this->output(
					'<STYLE><!--',
					'.qa-body-js-on .qa-notice {display:none;}',
					'//--></STYLE>'
				);
				//no menu but with sidebar
			if ((qa_opt('qat_sidebar_question') && ($this->template=='question')) || (qa_opt('qat_sidebar_other') && ($this->template!='question')) && (qa_opt('main_menu_position')!=1))
				$this->output(
					'<STYLE>',
'

.qa-main-wrapper {
    width: 620px !important;
}
.qa-q-item-main {
    width: 580px !important;
}
.qa-q-view-main {
    margin-right: 5px !important;
    width: 545px !important;
}
.qa-a-item-main {
    width: 545px !important;
}
.qa-a-item-c-list {
    width: 510px !important;
}

',
					'</STYLE>'
				);
		}

        function sidepanel()
        {
            if (qa_opt('main_menu_position')!=1){
                $this->output('<DIV CLASS="qa-nav-sidepanel">');
                //logo
                $this->output(
                    '<DIV CLASS="qa-logo">',
                    $this->content['logo'],
                    '</DIV>'
                );
                //menu
                $this->nav('main');           
                $this->output('</DIV>', '');
            }
        }
		
		function logo()
		{}
		
		function nav_user_search()
		{
			echo '<div class="qa-nav-useraccount">';
			$userid=qa_get_logged_in_userid();
				if (QA_FINAL_EXTERNAL_USERS)
					$avatar=qa_get_external_avatar_html($userid, $options['avatarsize'], false);
				else
				{
					$useraccount=qa_db_single_select(
						qa_db_user_account_selectspec( $userid,true)
					);	
					$avatar=qa_get_user_avatar_html(qa_get_logged_in_flags(), qa_get_logged_in_email(), qa_get_logged_in_handle(),
						@$useraccount["avatarblobid"], 25, 25, 35,false);
				}
				echo $avatar;

			$this->nav('user');
			echo '</div>';
			$this->search();
		}
		function main()
		{
			$this->output('<DIV CLASS="qa-main-wrapper'.(@$this->content['hidden'] ? ' qa-main-hidden' : '').'">');
			$this->nav('sub');
			qa_html_theme_base::main();
			$this->output('</DIV>');
			
			if (qa_opt('qat_sidebar_question') && ($this->template=='question')) 
				qa_html_theme_base::sidepanel();
			elseif (qa_opt('qat_sidebar_other') && ($this->template!='question')) 
				qa_html_theme_base::sidepanel();
				
		}
		function nav_main_sub()
		{
			if (qa_opt('main_menu_position')==1)
			{ 
				$this->output(
					'<div class="qa-nav-main-clear"> </div>',
					'<DIV CLASS="qa-logo">',
					$this->content['logo'],
					'</DIV>'
				);
				$this->nav('main');
				$this->output('<style type="text/css">');
				if ((qa_opt('qat_sidebar_question') && ($this->template=='question')) || (qa_opt('qat_sidebar_other') && ($this->template!='question'))){
					// if there is no menu but there is sidebar
					$this->output('
					.qa-main-wrapper {
						width: 720px !important;
					}
					.qa-q-item-main {
						width: 680px !important;
					}
					.qa-q-view-main {
						margin-right: 5px !important;
						width: 645px !important;
					}
					.qa-a-item-main {
						width: 645px !important;
					}
					.qa-a-item-c-list {
						width: 610px !important;
					}
				');
				}
				$this->output('
				.qa-nav-main-item{
					float: left;
				}
				.qa-nav-main-link{
					display: block;
				}
				.qa-nav-main-item:after{
					border-bottom: medium none;
				}
				.qa-nav-main {
					float: left;
					clear: none;
					margin-left: 10px;
				}
				.qa-logo {
					float: left;
				}
				.qa-main-wrapper{
					width: 970px;
				}
				.qa-q-item-main, .qa-q-view-main, .qa-a-item-main {
					width: 900px;
				}
				 .qa-a-item-c-list {
					width: 820px;
				}
				');
				
				$this->output('</style>');
			}
			//$this->nav('sub');
		}
		
		function post_avatar_meta_q($post, $class, $avatarprefix=null, $metaprefix=null, $metaseparator='<BR/>')
		{
			qa_html_theme_base::post_avatar_meta($post, $class, $avatarprefix=null, $metaprefix=null, $metaseparator='<BR/>');
			$this->q_item_stats($post);
		}
		
		function q_item_stats($q_item)
		{
			$this->output('<DIV CLASS="qa-q-item-stats">');
			
			if (isset($q_item['main_form_tags']))
				$this->output('<FORM '.$q_item['main_form_tags'].'>'); // form for voting buttons
			$this->voting($q_item);
			$this->a_count($q_item);
			$this->view_count_theme($q_item);
			if (isset($q_item['main_form_tags'])) {
				$this->form_hidden_elements(@$q_item['voting_form_hidden']);
				$this->output('</FORM>');
			}
	
			$this->output('</DIV>');
		}
		function view_count($post)
		{
		}
		function view_count_theme($post)
		{
			$this->output_split(@$post['views'], 'qa-view-count');
		}
		function post_avatar($post, $class, $prefix=null)
		{
		}
		function post_avatar_theme($post, $class, $prefix=null)
		{
			if (isset($post['avatar'])) {
				if (isset($prefix))
					$this->output($prefix);

				$this->output('<SPAN CLASS="'.$class.'-avatar">', $post['avatar'], '</SPAN>');
			}
		}		
		function q_list_item($q_item)
		{
			$this->output('<DIV CLASS="qa-q-list-item'.rtrim(' '.@$q_item['classes']).'" '.@$q_item['tags'].'>');

			//$this->q_item_stats($q_item);
			$this->post_avatar_theme($q_item, 'qa-q-item-avatar',null);
			$this->q_item_main($q_item);
			$this->q_item_clear();

			$this->output('</DIV> <!-- END qa-q-list-item -->', '');
		}
		function q_item_main($q_item)
		{
			$this->output('<DIV CLASS="qa-q-item-main">');
			
			$this->view_count($q_item);
			$this->q_item_title($q_item);
			$this->q_item_content($q_item);
			
			$this->output('<DIV CLASS="qa-q-item-avatar-metas">');
			$this->post_avatar_meta_q($q_item, 'qa-q-item');
			$this->output('</DIV>');
			
			$this->q_item_buttons($q_item);
				
			$this->output('</DIV>');
		}
		
		function q_item_content($q_item)
		{
			if (!empty($q_item['content'])) {
				$this->output('<DIV CLASS="qa-q-item-content">');
				$this->output_raw($q_item['content']);
				$this->post_tags($q_item, 'qa-q-item');
				$this->output('</DIV>');
			}
		}
		function q_view($q_view)
		{
			if (!empty($q_view)) {
				$this->output('<DIV CLASS="qa-q-view'.(@$q_view['hidden'] ? ' qa-q-view-hidden' : '').rtrim(' '.@$q_view['classes']).'"'.rtrim(' '.@$q_view['tags']).'>');
				/*
				if (isset($q_view['main_form_tags']))
					$this->output('<FORM '.$q_view['main_form_tags'].'>'); // form for voting buttons
				$this->voting($q_view);
				if (isset($q_view['main_form_tags']))
					$this->output('</FORM>');
				*/
				$this->a_count($q_view);
				$this->q_view_main($q_view);
				$this->q_view_clear();
				
				$this->output('</DIV> <!-- END qa-q-view -->', '');
			}
		}
		function q_view_main($q_view)
		{
			$this->post_avatar_theme($q_view, 'qa-q-view-avatar',null);
			$this->output('<DIV CLASS="qa-q-view-main">');

			$this->q_view_content($q_view);
			$this->q_view_extra($q_view);
			$this->q_view_follows($q_view);
			$this->q_view_closed($q_view);
			$this->output('<DIV CLASS="qa-q-view-avatar-metas">');
			$this->post_avatar_meta_q($q_view, 'qa-q-view');
			$this->output('</DIV>');
			
			$this->c_list(@$q_view['c_list'], 'qa-q-view');
			$this->c_form(@$q_view['c_form']);
			
			$this->output('</DIV> <!-- END qa-q-view-main -->');
			
			if (isset($q_view['main_form_tags']))
				$this->output('<FORM '.$q_view['main_form_tags'].'>'); // form for buttons on question
			$this->q_view_buttons($q_view);
			if (isset($q_view['main_form_tags'])) {
				$this->form_hidden_elements(@$q_view['buttons_form_hidden']);
				$this->output('</FORM>');
			}
				
			}
		function q_view_content($q_view)
		{
			if (!empty($q_view['content'])) {
				$this->output('<DIV CLASS="qa-q-view-content">');
				$this->output_raw($q_view['content']);
				$this->post_tags($q_view, 'qa-q-view');
				$this->output('</DIV>');
			}
		}
		function a_list_item($a_item)
		{
			$extraclass=@$a_item['classes'].($a_item['hidden'] ? ' qa-a-list-item-hidden' : ($a_item['selected'] ? ' qa-a-list-item-selected' : ''));
			
			$this->output('<DIV CLASS="qa-a-list-item '.$extraclass.'" '.@$a_item['tags'].'>');
			$this->post_avatar_theme($a_item, 'qa-a-item');	
					
			$this->a_item_main($a_item);
			$this->a_item_clear();

			$this->output('</DIV> <!-- END qa-a-list-item -->', '');
		}
		function a_item_main($a_item)
		{
			$this->output('<DIV CLASS="qa-a-item-main">');
			
			$this->error(@$a_item['error']);
			$this->a_item_content($a_item);				
				
			$this->post_avatar_meta_a($a_item, 'qa-a-item');
	

			
			$this->output('</DIV> <!-- END qa-a-item-main -->');
			
			$this->c_list(@$a_item['c_list'], 'qa-a-item');
			$this->c_form(@$a_item['c_form']);
		}
		
		function post_avatar_meta_a($post, $class, $avatarprefix=null, $metaprefix=null, $metaseparator='<BR/>')
		{
			qa_html_theme_base::post_avatar_meta($post, $class, $avatarprefix=null, $metaprefix=null, $metaseparator='<BR/>');
			
			if (isset($post['main_form_tags']))
				$this->output('<FORM '.$post['main_form_tags'].'>'); // form for buttons on answer
				
			$this->voting($post);
				
			if (isset($post['main_form_tags'])) {
				$this->form_hidden_elements(@$post['voting_form_hidden']);
				$this->output('</FORM>');
			}

			if (isset($post['main_form_tags']))
				$this->output('<FORM '.$post['main_form_tags'].'>'); // form for buttons on answer

			if ($post['hidden'])
				$this->output('<DIV CLASS="qa-a-item-hidden">');
			elseif ($post['selected'])
				$this->output('<DIV CLASS="qa-a-item-selected">');
			$this->a_selection($post);
			if ($post['hidden'] || $post['selected'])
				$this->output('</DIV>');
			$this->a_item_buttons($post);
			
			if (isset($post['main_form_tags'])) {
				$this->form_hidden_elements(@$post['buttons_form_hidden']);
				$this->output('</FORM>');
			}
		}
		function post_avatar_meta($post, $class, $avatarprefix=null, $metaprefix=null, $metaseparator='<BR/>')
		{
			$this->output('<SPAN CLASS="'.$class.'-avatar-meta">');
			$this->post_avatar_theme($post, $class, $avatarprefix);
			$this->post_meta($post, $class, $metaprefix, $metaseparator);
			$this->output('</SPAN>');
		}
		function attribution()
		{
			// you can disable this links in admin options
			if (!(qa_opt('qat_theme_attribution'))) 
				$this->output(
					'<DIV CLASS="qa-attribution">',
					', Theme by <A HREF="http://QA-Themes.com/" title="Free Q2A Themes">QA-Themes</A>',
					'</DIV>'
				);
			if (!(qa_opt('qat_qa_attribution'))) 
				qa_html_theme_base::attribution();
		}
}

	
/*
	Omit PHP closing tag to help avoid accidental output
*/