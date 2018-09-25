<?php

  function get_posts_filters() {
    $this->get_session();
    $this->load->helper('text');
    //captura los valores de la url separados por segmentos
    $filter = (null != $this->uri->segment(7)) ? $this->uri->segment(6) : $this->uri->segment(3);
    $filter2 = (null != $this->uri->segment(7)) ? $this->uri->segment(4) : '';
    /*
     * valores paginador
     * 
     */
    $page = $this->uri->segment(4);
    if (is_numeric($page)) {
      $items_per_page = ITEMS_PER_PAGE;
      // $registros = count($this->post_model->get_all_posts($this->uri->segment(3),$filter2, $this->uri->segment(4), '')->result());
      $registers = count($this->post_model->get_all_posts($filter, $filter2, $this->uri->segment(4), '')->result());
      $inicio = 0;
      $total_pages = ceil($registers / $items_per_page);
      $offset = ($page - 1 ) * $items_per_page;
      $limit = " LIMIT $offset, $items_per_page ";
    } else {
      $limit = "";
    }
    
    $posts = $this->post_model->get_all_posts($filter, $filter2, $this->uri->segment(4), $limit, '')->result();
    if (isset($this->session->userdata['user_id'])) {
      $favorites = $this->post_model->get_favorites_by_user($this->session->userdata['user_id'])->result();
    }

    foreach ($posts as $p) {
      $p->{'comments_cut'} = word_limiter(strip_tags($p->comments), 20, $end_char = '...');
      //Se obtiene la noticia vista por el usuario
      $p->{'visited'} = $this->post_model->get_post_views_by_user($p->id_post, $this->session->userdata['user_id']);
      //Se obtiene los tags relacionados a la noticia
      $p->{"tags_posts"} = $this->post_model->get_all_tags_posts($p->id_post)->result();
      foreach ($p->tags_posts as $tg) {
        $tg->{'total'} = $this->post_model->count_posts_by_tag_id($tg->id_tag)->result();
      }

      if ($favorites) {
        foreach ($favorites as $fav) {
          if ($fav->post_id == $p->id) {
            $p->{'is_favourite'} = true;
          }
        }
      }
      //Obtenemos la cantidad de favoritos por noticia
      $p->{'total_favorites'} = $this->post_model->get_all_favorites_by_post($p->id);
      //Obtenemos los votos de la noticia
      $p->{'votes_positives'} = $this->post_model->get_all_votes_by_post_id($p->id, 1);
      $p->{'votes_negatives'} = $this->post_model->get_all_votes_by_post_id($p->id, -1);
      $p->{'votes_by_users'} = $this->post_model->get_all_votes_by_users($p->id, $this->session->userdata['user_id']);
    }
    
    $this->load->model("tag_model");
    $tags_all = $this->tag_model->get_count_all_tags()->result();
    $this->twig->display('Frontend/index', array(
        "posts" => $posts,
        "total_pages" => (!isset($total_pages)) ? 0 : $total_pages,
        "active" => $this->uri->segment(4),
        "filtro" => ($this->uri->segment(7)) ? $this->uri->segment(6) : $this->uri->segment(3),
        "seccion" => $this->uri->segment(2),
        "session_id" => (isset($this->session->userdata['user_id'])) ? $this->session->userdata['user_id'] : 'no',
        "msg" => $this->session->flashdata('flash_message'),
        "tags" => $tags_all
    ));
  }
