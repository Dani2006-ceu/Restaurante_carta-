<?php
use App\Controllers\MenuController;
use App\Models\MenuItem;
use App\Models\PhotoStorage;

$currency = (string) $restaurant['currency'];
$value = static fn (string $field): string => h((string) ($editing[$field] ?? ''));
?>
<div class="panel">
    <header class="panel-head">
        <div>
            <p class="eyebrow">Panel del restaurante</p>
            <h1><?= h((string) $restaurant['name']) ?></h1>
        </div>
        <a class="button button-secondary" href="index.php?route=pedidos">
            Pedidos<?php if ($pendingOrders > 0): ?> <span class="nav-badge"><?= $pendingOrders ?></span><?php endif; ?>
        </a>
    </header>

    <div class="panel-grid">
        <section class="card panel-card" id="link">
            <h2>Link y QR del menú</h2>
            <?php if ($publicUrl === null): ?>
                <p class="muted">Genera un link para que tus clientes vean el menú desde el celular y hagan pedidos.</p>
                <form method="post" action="index.php?route=menu-link">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <button class="button" type="submit">Generar link</button>
                </form>
            <?php else: ?>
                <p class="muted">Comparte este link o imprime el QR. Tus clientes solo verán el menú y el botón para pedir.</p>
                <?php if ($localOnly): ?>
                    <p class="aviso-red">
                        Este link usa <strong>localhost</strong> y no abrirá desde un celular.
                        <?php if ($networkPanelUrl !== null): ?>
                            Abre el panel desde la red de tu casa en
                            <a href="<?= h($networkPanelUrl) ?>"><?= h($networkPanelUrl) ?></a>
                            (con el celular conectado al mismo Wi-Fi) y el link y el QR usarán esa dirección.
                        <?php endif; ?>
                        Cuando lo subas a InfinityFree, el link usará tu dominio.
                    </p>
                <?php endif; ?>
                <div class="link-box">
                    <input id="menu-link" type="text" value="<?= h($publicUrl) ?>" readonly aria-label="Link del menú">
                    <button class="button" type="button" data-copiar="#menu-link">Copiar</button>
                </div>
                <div class="qr-box">
                    <div class="qr-marco">
                        <div id="menu-qr" data-qr="<?= h($publicUrl) ?>" data-qr-titulo="<?= h((string) $restaurant['name']) ?>"
                             role="img" aria-label="Código QR del menú"></div>
                        <span><?= h((string) $restaurant['name']) ?></span>
                    </div>
                    <div class="qr-actions">
                        <a class="button button-secondary button-small" href="<?= h($publicUrl) ?>" target="_blank" rel="noopener">Ver menú</a>
                        <button class="button button-secondary button-small" type="button" data-descargar-qr="#menu-qr">Descargar QR</button>
                        <a class="button button-secondary button-small" href="https://wa.me/?text=<?= rawurlencode('Mira nuestro menú: ' . $publicUrl) ?>" target="_blank" rel="noopener">WhatsApp</a>
                    </div>
                </div>
                <form method="post" action="index.php?route=menu-link"
                      data-confirmar="El link y el QR actuales dejarán de funcionar. ¿Generar uno nuevo?">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <button class="button button-link" type="submit">Generar un link nuevo</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="card panel-card" id="producto">
            <h2><?= $editing ? 'Editar producto' : 'Agregar producto' ?></h2>
            <form method="post" action="index.php?route=menu-producto" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="item_id" value="<?= (int) $editing['id'] ?>">
                <?php endif; ?>

                <label for="category">Categoría</label>
                <input id="category" name="category" list="categorias" maxlength="60" required
                       placeholder="Entradas, Platos fuertes, Bebidas, Licores…" value="<?= $value('category') ?>">
                <datalist id="categorias">
                    <?php foreach (array_unique(array_merge(MenuController::CATEGORIES, array_keys($itemsByCategory))) as $category): ?>
                        <option value="<?= h((string) $category) ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <label for="name">Nombre</label>
                <input id="name" name="name" maxlength="120" required value="<?= $value('name') ?>">

                <label for="description">Descripción <span class="optional">(opcional)</span></label>
                <textarea id="description" name="description" maxlength="500" rows="2"><?= $value('description') ?></textarea>

                <div class="form-row">
                    <div>
                        <label for="price">Precio (<?= h($currency) ?>)</label>
                        <input id="price" name="price" inputmode="decimal" required
                               pattern="[0-9]+([.,][0-9]{1,2})?" placeholder="0.00" value="<?= $value('price') ?>">
                    </div>
                    <div>
                        <label for="discount_percent">Descuento %</label>
                        <input id="discount_percent" name="discount_percent" type="number" min="0" max="100"
                               inputmode="numeric" value="<?= $editing ? (int) $editing['discount_percent'] : 0 ?>">
                    </div>
                </div>

                <?php
                $currentPhoto = $editing['image_url'] ?? null;
                $photoIsLink = $currentPhoto !== null && !PhotoStorage::isLocal($currentPhoto);
                ?>
                <span class="field-label">Foto <span class="optional">(opcional)</span></span>
                <div class="foto-campo">
                    <div class="foto-preview" data-foto-preview>
                        <?php if ($currentPhoto !== null): ?>
                            <img src="<?= h($currentPhoto) ?>" alt="Foto actual">
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-8 8"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="foto-opciones">
                        <label class="button button-secondary button-small subir-foto" for="photo">
                            <?= $currentPhoto !== null ? 'Cambiar foto' : 'Subir o tomar foto' ?>
                        </label>
                        <input id="photo" class="file-input" name="photo" type="file"
                               accept="image/jpeg,image/png,image/webp,image/gif" data-foto>
                        <small class="foto-ayuda" data-foto-estado>JPG, PNG o WebP. Se achica sola para que cargue rápido.</small>
                        <?php if ($currentPhoto !== null): ?>
                            <label class="check">
                                <input type="checkbox" name="remove_photo"> Quitar foto
                            </label>
                        <?php endif; ?>
                    </div>
                </div>

                <label for="image_url">O pega el link de una imagen <span class="optional">(opcional)</span></label>
                <input id="image_url" name="image_url" type="url" maxlength="500"
                       placeholder="https://…" value="<?= $photoIsLink ? h((string) $currentPhoto) : '' ?>">

                <label class="check">
                    <input type="checkbox" name="is_available" <?= !$editing || (int) $editing['is_available'] === 1 ? 'checked' : '' ?>>
                    Disponible para pedir
                </label>

                <button class="button" type="submit"><?= $editing ? 'Guardar cambios' : 'Agregar al menú' ?></button>
                <?php if ($editing): ?>
                    <a class="button button-link" href="index.php?route=menu">Cancelar</a>
                <?php endif; ?>
            </form>
        </section>
    </div>

    <section class="card panel-card">
        <h2>Productos del menú</h2>
        <?php if ($itemsByCategory === []): ?>
            <p class="muted">Todavía no hay productos. Agrega tus platos, bebidas, licores y lo que vendas.</p>
        <?php endif; ?>
        <?php foreach ($itemsByCategory as $category => $items): ?>
            <h3 class="category-title"><?= h((string) $category) ?> <span>(<?= count($items) ?>)</span></h3>
            <ul class="item-list">
                <?php foreach ($items as $item): ?>
                    <?php $final = MenuItem::finalPrice($item); ?>
                    <li class="item-row<?= (int) $item['is_available'] === 1 ? '' : ' is-off' ?><?= $item['image_url'] !== null ? ' con-foto' : '' ?>">
                        <?php if ($item['image_url'] !== null): ?>
                            <img class="item-thumb" src="<?= h((string) $item['image_url']) ?>" alt="" loading="lazy">
                        <?php endif; ?>
                        <div class="item-info">
                            <strong><?= h((string) $item['name']) ?></strong>
                            <?php if ((int) $item['is_available'] !== 1): ?><span class="tag">No disponible</span><?php endif; ?>
                            <?php if ((int) $item['discount_percent'] > 0): ?><span class="tag tag-discount">-<?= (int) $item['discount_percent'] ?>%</span><?php endif; ?>
                            <?php if ($item['description'] !== null): ?><small><?= h((string) $item['description']) ?></small><?php endif; ?>
                        </div>
                        <div class="item-price">
                            <?php if ((int) $item['discount_percent'] > 0): ?>
                                <s><?= h(money((float) $item['price'], $currency)) ?></s>
                            <?php endif; ?>
                            <strong><?= h(money($final, $currency)) ?></strong>
                        </div>
                        <div class="item-actions">
                            <a class="button button-secondary" href="index.php?route=menu&amp;editar=<?= (int) $item['id'] ?>#producto">Editar</a>
                            <form method="post" action="index.php?route=menu-producto-eliminar"
                                  data-confirmar="¿Eliminar «<?= h((string) $item['name']) ?>» del menú?">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <button class="button button-danger" type="submit">Eliminar</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </section>

    <section class="card panel-card">
        <h2>Datos del restaurante</h2>
        <p class="muted">Se muestran arriba del menú que ven tus clientes.</p>
        <form method="post" action="index.php?route=menu-ajustes">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <div class="form-row">
                <div>
                    <label for="r-name">Nombre del restaurante</label>
                    <input id="r-name" name="name" maxlength="120" required value="<?= h((string) $restaurant['name']) ?>">
                </div>
                <div>
                    <label for="r-currency">Moneda</label>
                    <input id="r-currency" name="currency" maxlength="5" placeholder="$, €, S/, Bs…" value="<?= h($currency) ?>">
                </div>
            </div>
            <label for="r-description">Descripción corta <span class="optional">(opcional)</span></label>
            <input id="r-description" name="description" maxlength="300" value="<?= h((string) ($restaurant['description'] ?? '')) ?>">
            <div class="form-row">
                <div>
                    <label for="r-phone">Teléfono / WhatsApp <span class="optional">(opcional)</span></label>
                    <input id="r-phone" name="phone" type="tel" maxlength="30" value="<?= h((string) ($restaurant['phone'] ?? '')) ?>">
                </div>
                <div>
                    <label for="r-hours">Horario <span class="optional">(opcional)</span></label>
                    <input id="r-hours" name="opening_hours" maxlength="120" placeholder="Lun a Sáb 12:00 – 22:00" value="<?= h((string) ($restaurant['opening_hours'] ?? '')) ?>">
                </div>
            </div>
            <label for="r-address">Dirección <span class="optional">(opcional)</span></label>
            <input id="r-address" name="address" maxlength="200" value="<?= h((string) ($restaurant['address'] ?? '')) ?>">
            <button class="button" type="submit">Guardar datos</button>
        </form>
    </section>
</div>

<script src="assets/vendor/qrcode.min.js" defer></script>
