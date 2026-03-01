<div <?= \ReactSmith\Core\Helpers::build_attributes($args['attributes']); ?>>
    <div class="rs-card-stack__inner">

        <?php if (!empty($args['stack'])) : ?>
        <ul <?= \ReactSmith\Core\Helpers::build_attributes($args['list_attributes']); ?>>
            <?php foreach ($args['stack'] as $card) : ?>
               <li class="rs-card-stack__item">

                   <div class="rs-card rs-card--style-full">
                       <div class="rs-card__inner">
                           <div class="rs-card__image">
                               <a target="_blank" href="<?= $card['link']; ?>" title="<?= $card['title']; ?>">
                                   <img src="<?= $card['image_url']; ?>" alt="<?= $card['title']; ?>" />
                               </a>
                           </div>
                       </div>
                   </div>

               </li>

            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
