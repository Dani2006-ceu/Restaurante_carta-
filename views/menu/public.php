<?php
use App\Controllers\PublicMenuController;
use App\Models\Order;

$currency = (string) $restaurant['currency'];
$token = (string) $restaurant['public_token'];
$phoneDigits = preg_replace('/\D+/', '', (string) ($restaurant['phone'] ?? ''));
$slug = static fn (string $text): string => 'cat-' . substr(md5($text), 0, 8);
?>
<header class="carta-head">
    <?= logo_svg('carta-logo') ?>
    <p class="carta-eyebrow">Menú</p>
    <h1><?= h((string) $restaurant['name']) ?></h1>
    <div class="ornamento" aria-hidden="true"><span></span>◆<span></span></div>
    <?php if ($restaurant['description'] !== null): ?>
        <p class="carta-descripcion"><?= h((string) $restaurant['description']) ?></p>
    <?php endif; ?>
    <ul class="carta-datos">
        <?php if ($restaurant['opening_hours'] !== null): ?><li><?= h((string) $restaurant['opening_hours']) ?></li><?php endif; ?>
        <?php if ($restaurant['address'] !== null): ?><li><?= h((string) $restaurant['address']) ?></li><?php endif; ?>
        <?php if ($phoneDigits !== ''): ?>
            <li><a href="https://wa.me/<?= h($phoneDigits) ?>" target="_blank" rel="noopener"><?= h((string) $restaurant['phone']) ?></a></li>
        <?php endif; ?>
    </ul>
    <div class="carta-acciones">
        <button class="carta-chip" type="button" data-instalar hidden>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg>
            Guardar en el celular
        </button>
        <button class="carta-chip" type="button" data-imprimir>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6"/></svg>
            Descargar PDF
        </button>
    </div>
</header>

<?php if ($orderSent): ?>
    <section class="carta-exito" role="status">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m7.5 12.5 3 3 6-6"/></svg>
        <h2>¡Pedido enviado!</h2>
        <p>El restaurante ya recibió tu pedido.</p>
        <a class="carta-boton" href="index.php?route=carta&amp;m=<?= h($token) ?>">Hacer otro pedido</a>
    </section>
<?php elseif ($itemsByCategory === []): ?>
    <p class="carta-vacio">El menú aún no tiene productos disponibles.</p>
<?php else: ?>
    <nav class="carta-categorias" aria-label="Categorías">
        <?php foreach (array_keys($itemsByCategory) as $category): ?>
            <a href="#<?= $slug((string) $category) ?>"><?= h((string) $category) ?></a>
        <?php endforeach; ?>
    </nav>

    <form class="carta" method="post" action="index.php?route=carta-pedido&amp;m=<?= h($token) ?>"
          data-moneda="<?= h($currency) ?>">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

        <?php foreach ($itemsByCategory as $category => $items): ?>
            <section class="carta-seccion" id="<?= $slug((string) $category) ?>">
                <h2><?= h((string) $category) ?></h2>
                <?php foreach ($items as $item): ?>
                    <article class="producto<?= $item['image_url'] !== null ? ' con-foto' : '' ?>" data-producto="<?= (int) $item['id'] ?>"
                             data-nombre="<?= h((string) $item['name']) ?>" data-precio="<?= h((string) $item['final_price']) ?>">
                        <?php if ($item['image_url'] !== null): ?>
                            <img src="<?= h((string) $item['image_url']) ?>" alt="" loading="lazy">
                        <?php endif; ?>
                        <div class="producto-info">
                            <h3><?= h((string) $item['name']) ?></h3>
                            <?php if ($item['description'] !== null): ?>
                                <p><?= h((string) $item['description']) ?></p>
                            <?php endif; ?>
                            <div class="producto-precio">
                                <strong><?= h(money((float) $item['final_price'], $currency)) ?></strong>
                                <?php if ((int) $item['discount_percent'] > 0): ?>
                                    <s><?= h(money((float) $item['price'], $currency)) ?></s>
                                    <span class="descuento">-<?= (int) $item['discount_percent'] ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="cantidad">
                            <label class="sr-only" for="q<?= (int) $item['id'] ?>">Cantidad de <?= h((string) $item['name']) ?></label>
                            <input id="q<?= (int) $item['id'] ?>" name="items[<?= (int) $item['id'] ?>]"
                                   type="number" min="0" max="<?= PublicMenuController::MAX_QUANTITY ?>" value="0" inputmode="numeric">
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>

        <div class="carrito-barra" hidden>
            <button type="button" class="carta-boton" data-abrir-pedido>
                <span>Ver pedido · <b data-total-cantidad>0</b></span>
                <b data-total-precio></b>
            </button>
        </div>

        <section class="pedido-hoja" id="pedido" aria-labelledby="pedido-titulo">
            <div class="pedido-contenido">
                <header>
                    <h2 id="pedido-titulo">Tu pedido</h2>
                    <button type="button" class="cerrar" data-cerrar-pedido aria-label="Cerrar">×</button>
                </header>
                <ul class="pedido-lista" data-pedido-lista></ul>
                <p class="pedido-total">Total <strong data-total-precio></strong></p>

                <label for="customer_name">Tu nombre</label>
                <input id="customer_name" name="customer_name" maxlength="80" required autocomplete="name">

                <fieldset class="tipo-pedido">
                    <legend>¿Cómo lo quieres?</legend>
                    <?php foreach (Order::TYPES as $value => $label): ?>
                        <label>
                            <input type="radio" name="order_type" value="<?= h($value) ?>" <?= $value === 'mesa' ? 'checked' : '' ?>>
                            <span><?= h($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <label for="table_label" data-etiqueta-ubicacion>Número de mesa</label>
                <input id="table_label" name="table_label" maxlength="120">

                <label for="customer_phone">Teléfono <span class="opcional">(opcional)</span></label>
                <input id="customer_phone" name="customer_phone" type="tel" maxlength="30" autocomplete="tel">

                <label for="note">Nota <span class="opcional">(opcional)</span></label>
                <textarea id="note" name="note" maxlength="500" rows="2" placeholder="Sin cebolla, bien cocido…"></textarea>

                <button class="carta-boton carta-enviar" type="submit">Enviar pedido</button>
            </div>
        </section>
    </form>
<?php endif; ?>
