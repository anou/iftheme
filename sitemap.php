<?php /* Template Name: Sitemap */ ?>

<?php get_header(); ?>

<div id="content">
  <h1><?php the_title(); ?></h1>
 
  <div class="bxshadow">
    <h2><?php _e('Pages', 'iftheme'); ?></h2>
    <ul><?php wp_list_pages("title_li=" ); ?></ul>
  </div>
 <!-- <h2><?php _e('RSS Feeds', 'iftheme'); ?></h2>
  <ul>
    <li><a title="Full content" href="feed:<?php bloginfo('rss2_url'); ?>"><?php _e('Main RSS' , 'iftheme'); ?></a></li>
    <li><a title="Comment Feed" href="feed:<?php bloginfo('comments_rss2_url'); ?>"><?php _e('Comment Feed', 'iftheme'); ?></a></li>
  </ul>
 -->
  <div class="bxshadow">
    <h2><?php _e('Categories', 'iftheme'); ?></h2>
    <ul><?php wp_list_categories('sort_column=name&feed=RSS&hide_empty=0&title_li='); ?></ul>
  </div>

  <div class="bxshadow">
    <h2><?php _e('All Blog Posts', 'iftheme'); ?></h2>
    <ul><?php $archive_query = new WP_Query('showposts=1000'); while ($archive_query->have_posts()) : $archive_query->the_post(); ?>
      <li><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php sprintf( _e("Permanent Link to %s" , 'iftheme') , the_title()); ?>"><?php the_title(); ?></a> </li>
      <?php endwhile; ?>
    </ul>
  </div>


</div><!--#content-->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
