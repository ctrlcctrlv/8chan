<?php
	require 'info.php';
	
	function ukko_build($action, $settings) {
		$ukko = new ukko();
		$ukko->settings = $settings;
		
		file_write($settings['uri'] . '/index.html', $ukko->build());
		file_write($settings['uri'] . '/ukko.js', Element('themes/ukko/ukko.js', array()));
	}
	
	class ukko {
		public $settings;
		public function build($mod = false) {
			global $config, $board;
			$boards = listBoards();
			
			$body = '';
			$overflow = array();
			$board = array(
				'url' => $this->settings['uri'],
				'name' => $this->settings['title'],
				'title' => sprintf($this->settings['subtitle'], $this->settings['thread_limit'])
			);

			$query = '';
			foreach($boards as &$_board) {
				if(in_array($_board['uri'], explode(' ', $this->settings['exclude'])))
					continue;
				$query .= sprintf("SELECT * FROM ``posts`` WHERE `board` = '%s' AND `thread` IS NULL UNION ALL ", $_board['uri']);
			}
			$query = preg_replace('/UNION ALL $/', 'ORDER BY `bump` DESC', $query);
			$query = query($query) or error(db_error());

			$count = 0;
			$threads = array();
			while($post = $query->fetch()) {

				if(!isset($threads[$post['board']])) {
					$threads[$post['board']] = 1;
				} else {
					$threads[$post['board']] += 1;
				}
	
				if($count < $this->settings['thread_limit']) {				
					$config['uri_thumb'] = '/'.$post['board'].'/thumb/';
					$config['uri_img'] = '/'.$post['board'].'/src/';
					$board['dir'] = $post['board'].'/';
					$thread = new Thread($post, $mod ? '?/' : $config['root'], $mod);

					$posts = prepare("SELECT * FROM ``posts`` WHERE `board` = :board AND `thread` = :id_for_board ORDER BY `id_for_board` DESC LIMIT :limit");
					$posts->bindValue(':id_for_board', $post['id_for_board']);
					$posts->bindValue(':board', $post['board']);
					$posts->bindValue(':limit', ($post['sticky'] ? $config['threads_preview_sticky'] : $config['threads_preview']), PDO::PARAM_INT);
					$posts->execute() or error(db_error($posts));
					
					$num_images = 0;
					while ($po = $posts->fetch()) {
						$config['uri_thumb'] = '/'.$post['board'].'/thumb/';
						$config['uri_img'] = '/'.$post['board'].'/src/';

						if ($po['files'])
							$num_images++;
						
						$thread->add(new Post($po, $mod ? '?/' : $config['root'], $mod));
					
					}
					if ($posts->rowCount() == ($post['sticky'] ? $config['threads_preview_sticky'] : $config['threads_preview'])) {
						$ct = prepare("SELECT COUNT(`id_for_board`) as `num` FROM ``posts`` WHERE `board` = :board AND `thread` = :thread UNION ALL SELECT COUNT(`id_for_board`) FROM ``posts`` WHERE `board` = :board AND `files` IS NOT NULL AND `thread` = :thread");
						$ct->bindValue(':thread', $post['id_for_board'], PDO::PARAM_INT);
						$ct->bindValue(':board', $post['board']);
						$ct->execute() or error(db_error($count));
						
						$c = $ct->fetch();
						$thread->omitted = $c['num'] - ($post['sticky'] ? $config['threads_preview_sticky'] : $config['threads_preview']);
						
						$c = $ct->fetch();
						$thread->omitted_images = $c['num'] - $num_images;
					}


					$thread->posts = array_reverse($thread->posts);
					$body .= '<h2><a href="' . $config['root'] . $post['board'] . '">/' . $post['board'] . '/</a></h2>';
					$body .= $thread->build(true);
				} else {
					$page = 'index';
					if(floor($threads[$post['board']] / $config['threads_per_page']) > 0) {
						$page = floor($threads[$post['board']] / $config['threads_per_page']) + 1;
					}
					$overflow[] = array('id' => $post['id_for_board'], 'board' => $post['board'], 'page' => $page . '.html');
				}

				$count += 1;
			}

			$body .= '<script> var overflow = ' . json_encode($overflow) . '</script>';
			$body .= '<script type="text/javascript" src="/'.$this->settings['uri'].'/ukko.js"></script>';

			$config['default_stylesheet'] = array('Yotsuba B', $config['stylesheets']['Yotsuba B']);

			return Element('index.html', array(
				'config' => $config,
				'board' => $board,
				'no_post_form' => true,
				'body' => $body,
				'mod' => $mod,
				'boardlist' => createBoardlist($mod),
			));
		}
		
	};
	
?>
