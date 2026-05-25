CREATE TABLE IF NOT EXISTS belief_doctrines (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  summary TEXT NULL,
  image_url VARCHAR(500) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_belief_doctrines_active_order (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS belief_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  doctrine_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  slug VARCHAR(200) NOT NULL,
  content MEDIUMTEXT NOT NULL,
  bible_references TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_belief_items_doctrine_slug (doctrine_id, slug),
  INDEX idx_belief_items_doctrine_active_order (doctrine_id, is_active, sort_order),
  CONSTRAINT fk_belief_items_doctrine
    FOREIGN KEY (doctrine_id) REFERENCES belief_doctrines(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO belief_doctrines (title, slug, summary, image_url, sort_order, is_active)
SELECT 'Dios', 'dios', 'La revelación bíblica acerca del Padre, el Hijo y el Espíritu Santo, fundamento de la fe y la adoración cristiana.', '/assets/images/beliefs/dios.svg', 10, 1
WHERE NOT EXISTS (SELECT 1 FROM belief_doctrines WHERE slug = 'dios');

INSERT INTO belief_doctrines (title, slug, summary, image_url, sort_order, is_active)
SELECT 'Humanidad', 'humanidad', 'La creación, dignidad, caída y restauración de las personas según el propósito amoroso de Dios.', '/assets/images/beliefs/humanidad.svg', 20, 1
WHERE NOT EXISTS (SELECT 1 FROM belief_doctrines WHERE slug = 'humanidad');

INSERT INTO belief_doctrines (title, slug, summary, image_url, sort_order, is_active)
SELECT 'Salvación', 'salvacion', 'La obra redentora de Cristo, la experiencia de la gracia, la fe y la vida nueva en el evangelio.', '/assets/images/beliefs/salvacion.svg', 30, 1
WHERE NOT EXISTS (SELECT 1 FROM belief_doctrines WHERE slug = 'salvacion');

INSERT INTO belief_doctrines (title, slug, summary, image_url, sort_order, is_active)
SELECT 'Iglesia', 'iglesia', 'La comunidad de creyentes llamada a adorar, servir, testificar y vivir en unidad bajo Cristo.', '/assets/images/beliefs/iglesia.svg', 40, 1
WHERE NOT EXISTS (SELECT 1 FROM belief_doctrines WHERE slug = 'iglesia');

INSERT INTO belief_doctrines (title, slug, summary, image_url, sort_order, is_active)
SELECT 'Vida cristiana', 'vida-cristiana', 'La respuesta diaria del creyente en adoración, mayordomía, salud, familia y servicio.', '/assets/images/beliefs/vida-cristiana.svg', 50, 1
WHERE NOT EXISTS (SELECT 1 FROM belief_doctrines WHERE slug = 'vida-cristiana');

INSERT INTO belief_doctrines (title, slug, summary, image_url, sort_order, is_active)
SELECT 'Acontecimientos finales', 'acontecimientos-finales', 'La esperanza bíblica del regreso de Cristo, la restauración final y la vida eterna.', '/assets/images/beliefs/acontecimientos-finales.svg', 60, 1
WHERE NOT EXISTS (SELECT 1 FROM belief_doctrines WHERE slug = 'acontecimientos-finales');

INSERT INTO belief_items (doctrine_id, title, slug, content, bible_references, sort_order, is_active)
SELECT d.id, x.title, x.slug, x.content, x.refs, x.sort_order, 1
FROM belief_doctrines d
JOIN (
  SELECT 'dios' doctrine_slug, 'Las Sagradas Escrituras' title, 'las-sagradas-escrituras' slug, 'La Biblia es la Palabra inspirada de Dios y la autoridad segura para conocer su voluntad, orientar la fe y vivir conforme al evangelio.' content, 'Salmo 119:105; 2 Timoteo 3:16-17; Hebreos 4:12; 2 Pedro 1:20-21' refs, 10 sort_order
  UNION ALL SELECT 'dios', 'La Trinidad', 'la-trinidad', 'Hay un solo Dios: Padre, Hijo y Espíritu Santo. Dios es eterno, santo, amoroso y digno de adoración, y actúa unido en la creación, redención y restauración.', 'Deuteronomio 6:4; Mateo 28:19; 2 Corintios 13:14; Efesios 4:4-6', 20
  UNION ALL SELECT 'dios', 'Dios el Padre', 'dios-el-padre', 'El Padre es el Creador, sustentador y soberano de todo. Su carácter se revela plenamente en Jesucristo y en su trato misericordioso con la humanidad.', 'Génesis 1:1; Juan 3:16; Juan 14:9; 1 Juan 4:8', 30
  UNION ALL SELECT 'dios', 'Dios el Hijo', 'dios-el-hijo', 'Jesucristo es Dios eterno hecho hombre. Vivió sin pecado, murió por la humanidad, resucitó y ministra por nosotros hasta su regreso.', 'Juan 1:1-14; Colosenses 1:15-20; Hebreos 4:14-16; 1 Corintios 15:3-4', 40
  UNION ALL SELECT 'dios', 'Dios el Espíritu Santo', 'dios-el-espiritu-santo', 'El Espíritu Santo inspiró las Escrituras, convence de pecado, guía a la verdad, concede dones y transforma la vida de los creyentes.', 'Juan 14:16-17; Juan 16:8-13; Hechos 1:8; Gálatas 5:22-23', 50
) x ON x.doctrine_slug = d.slug
WHERE NOT EXISTS (
  SELECT 1 FROM belief_items i WHERE i.doctrine_id = d.id AND i.slug = x.slug
);

INSERT INTO belief_items (doctrine_id, title, slug, content, bible_references, sort_order, is_active)
SELECT d.id, x.title, x.slug, x.content, x.refs, x.sort_order, 1
FROM belief_doctrines d
JOIN (
  SELECT 'humanidad' doctrine_slug, 'La creación' title, 'la-creacion' slug, 'Dios creó el mundo y la vida por su palabra. El sábado recuerda su obra creadora y llama a la humanidad a adorarlo como Creador.' content, 'Génesis 1-2; Exodo 20:8-11; Salmo 33:6-9; Apocalipsis 14:7' refs, 10 sort_order
  UNION ALL SELECT 'humanidad', 'La naturaleza humana', 'la-naturaleza-humana', 'Las personas fueron creadas a imagen de Dios, con dignidad y libertad. El pecado dañó esa imagen, pero Dios obra para restaurarla por medio de Cristo.', 'Génesis 1:26-28; Génesis 3; Romanos 5:12-17; Efesios 2:1-10', 20
) x ON x.doctrine_slug = d.slug
WHERE NOT EXISTS (
  SELECT 1 FROM belief_items i WHERE i.doctrine_id = d.id AND i.slug = x.slug
);

INSERT INTO belief_items (doctrine_id, title, slug, content, bible_references, sort_order, is_active)
SELECT d.id, x.title, x.slug, x.content, x.refs, x.sort_order, 1
FROM belief_doctrines d
JOIN (
  SELECT 'salvacion' doctrine_slug, 'El gran conflicto' title, 'el-gran-conflicto' slug, 'La Biblia presenta un conflicto entre Cristo y Satanás acerca del carácter de Dios, su ley y su gobierno. Cristo asegura la victoria final de Dios.' content, 'Apocalipsis 12:7-12; Genesis 3:15; Job 1-2; 1 Pedro 5:8; 1 Juan 3:8' refs, 10 sort_order
  UNION ALL SELECT 'salvacion', 'Vida, muerte y resurrección de Cristo', 'vida-muerte-y-resurreccion-de-cristo', 'La vida perfecta, la muerte sustitutiva y la resurrección de Jesús son el centro de la salvación. En Cristo hay perdón, reconciliación y esperanza.', 'Isaías 53; Juan 3:16; Romanos 3:21-26; 1 Corintios 15:3-4', 20
  UNION ALL SELECT 'salvacion', 'La experiencia de la salvación', 'la-experiencia-de-la-salvacion', 'La salvación es un regalo de la gracia de Dios recibido por fe. El Espíritu Santo produce arrepentimiento, nuevo nacimiento y crecimiento en Cristo.', 'Juan 3:3-8; Romanos 8:1-4; 2 Corintios 5:17-21; Efesios 2:8-10', 30
  UNION ALL SELECT 'salvacion', 'Crecer en Cristo', 'crecer-en-cristo', 'Quienes pertenecen a Cristo viven bajo su señorío. La oración, la Palabra y la comunión fortalecen una vida de victoria, servicio y confianza.', 'Juan 15:1-10; Efesios 6:10-18; Colosenses 2:6-7; 2 Pedro 3:18', 40
) x ON x.doctrine_slug = d.slug
WHERE NOT EXISTS (
  SELECT 1 FROM belief_items i WHERE i.doctrine_id = d.id AND i.slug = x.slug
);

INSERT INTO belief_items (doctrine_id, title, slug, content, bible_references, sort_order, is_active)
SELECT d.id, x.title, x.slug, x.content, x.refs, x.sort_order, 1
FROM belief_doctrines d
JOIN (
  SELECT 'iglesia' doctrine_slug, 'La iglesia' title, 'la-iglesia' slug, 'La iglesia es la comunidad de creyentes que confiesa a Jesús como Salvador y Señor, reunida para adorar, aprender, servir y anunciar el evangelio.' content, 'Mateo 16:18; Hechos 2:42-47; Efesios 1:22-23; Efesios 4:11-16' refs, 10 sort_order
  UNION ALL SELECT 'iglesia', 'El remanente y su misión', 'el-remanente-y-su-mision', 'Dios llama a su pueblo a proclamar el evangelio eterno, invitar a la fidelidad bíblica y preparar al mundo para el regreso de Cristo.', 'Daniel 7:9-14; Apocalipsis 12:17; Apocalipsis 14:6-12; Mateo 24:14', 20
  UNION ALL SELECT 'iglesia', 'Unidad en el cuerpo de Cristo', 'unidad-en-el-cuerpo-de-cristo', 'En Cristo, los creyentes forman un solo cuerpo. La unidad cristiana supera barreras culturales, sociales y personales sin borrar la diversidad de dones.', 'Juan 17:20-23; 1 Corintios 12:12-27; Gálatas 3:26-29; Efesios 4:1-6', 30
  UNION ALL SELECT 'iglesia', 'El bautismo', 'el-bautismo', 'El bautismo por inmersión expresa fe en la muerte y resurrección de Cristo, arrepentimiento, nuevo nacimiento y entrada a la comunidad de creyentes.', 'Mateo 28:19-20; Hechos 2:38; Romanos 6:1-6; Colosenses 2:12', 40
  UNION ALL SELECT 'iglesia', 'La Cena del Señor', 'la-cena-del-senor', 'La Cena del Señor recuerda el sacrificio de Cristo, renueva la comunión con él y con la iglesia, y anticipa el banquete del reino de Dios.', 'Juan 13:1-17; 1 Corintios 10:16-17; 1 Corintios 11:23-30; Apocalipsis 19:9', 50
  UNION ALL SELECT 'iglesia', 'Dones y ministerios espirituales', 'dones-y-ministerios-espirituales', 'El Espíritu Santo concede dones a todos los creyentes para edificar la iglesia, servir con amor y cumplir la misión de Cristo.', 'Romanos 12:4-8; 1 Corintios 12; Efesios 4:8-13; 1 Pedro 4:10-11', 60
  UNION ALL SELECT 'iglesia', 'El don de profecía', 'el-don-de-profecia', 'La Biblia enseña que el don profético acompaña al pueblo de Dios. Su función es orientar hacia Cristo, fortalecer la fe y someterse siempre a la Escritura.', 'Joel 2:28-29; Hechos 2:14-21; 1 Tesalonicenses 5:19-21; Apocalipsis 19:10', 70
) x ON x.doctrine_slug = d.slug
WHERE NOT EXISTS (
  SELECT 1 FROM belief_items i WHERE i.doctrine_id = d.id AND i.slug = x.slug
);

INSERT INTO belief_items (doctrine_id, title, slug, content, bible_references, sort_order, is_active)
SELECT d.id, x.title, x.slug, x.content, x.refs, x.sort_order, 1
FROM belief_doctrines d
JOIN (
  SELECT 'vida-cristiana' doctrine_slug, 'La ley de Dios' title, 'la-ley-de-dios' slug, 'La ley de Dios expresa su carácter de amor y muestra una vida de fidelidad. La obediencia nace de la gracia y de una relación viva con Cristo.' content, 'Exodo 20:1-17; Mateo 22:36-40; Juan 14:15; Romanos 8:3-4' refs, 10 sort_order
  UNION ALL SELECT 'vida-cristiana', 'El sábado', 'el-sabado', 'El sábado, séptimo día de la semana, es un regalo de descanso, adoración y comunión que celebra a Dios como Creador y Redentor.', 'Génesis 2:1-3; Exodo 20:8-11; Isaías 58:13-14; Marcos 2:27-28', 20
  UNION ALL SELECT 'vida-cristiana', 'La mayordomía', 'la-mayordomia', 'Todo lo que somos y tenemos pertenece a Dios. La mayordomía cristiana incluye tiempo, talentos, recursos, cuerpo y cuidado de la creación.', 'Deuteronomio 8:18; Malaquías 3:8-10; Mateo 25:14-30; 1 Pedro 4:10', 30
  UNION ALL SELECT 'vida-cristiana', 'La conducta cristiana', 'la-conducta-cristiana', 'La vida cristiana busca honrar a Dios en pensamientos, relaciones, hábitos y decisiones. El creyente es llamado a vivir con pureza, humildad y servicio.', 'Romanos 12:1-2; 1 Corintios 10:31; 2 Corintios 7:1; Filipenses 4:8', 40
  UNION ALL SELECT 'vida-cristiana', 'El matrimonio y la familia', 'el-matrimonio-y-la-familia', 'El matrimonio y la familia son dones de Dios para expresar amor, fidelidad, cuidado y formación espiritual en el hogar.', 'Génesis 2:18-24; Mateo 19:3-6; Efesios 5:21-33; Efesios 6:1-4', 50
) x ON x.doctrine_slug = d.slug
WHERE NOT EXISTS (
  SELECT 1 FROM belief_items i WHERE i.doctrine_id = d.id AND i.slug = x.slug
);

INSERT INTO belief_items (doctrine_id, title, slug, content, bible_references, sort_order, is_active)
SELECT d.id, x.title, x.slug, x.content, x.refs, x.sort_order, 1
FROM belief_doctrines d
JOIN (
  SELECT 'acontecimientos-finales' doctrine_slug, 'El ministerio de Cristo en el santuario celestial' title, 'el-ministerio-de-cristo-en-el-santuario-celestial' slug, 'Cristo ministra en favor de su pueblo como Sumo Sacerdote. Su obra asegura perdón, juicio justo y la consumación de la salvación.' content, 'Hebreos 4:14-16; Hebreos 8:1-2; Daniel 7:9-14; 1 Juan 2:1' refs, 10 sort_order
  UNION ALL SELECT 'acontecimientos-finales', 'La segunda venida de Cristo', 'la-segunda-venida-de-cristo', 'El regreso de Jesús será real, visible y glorioso. Esta esperanza anima a la iglesia a vivir con fidelidad y misión.', 'Mateo 24:30-31; Juan 14:1-3; Hechos 1:9-11; 1 Tesalonicenses 4:13-18', 20
  UNION ALL SELECT 'acontecimientos-finales', 'Muerte y resurrección', 'muerte-y-resurreccion', 'La muerte es un estado inconsciente hasta la resurrección. En la venida de Cristo, los justos resucitarán para vida eterna.', 'Eclesiastés 9:5-6; Juan 5:28-29; 1 Corintios 15:51-54; 1 Tesalonicenses 4:16-17', 30
  UNION ALL SELECT 'acontecimientos-finales', 'El milenio y el fin del pecado', 'el-milenio-y-el-fin-del-pecado', 'La Biblia anuncia un período de mil años y el juicio final, después del cual Dios eliminará definitivamente el pecado y sus consecuencias.', 'Apocalipsis 20; 1 Corintios 6:2-3; Malaquías 4:1; 2 Pedro 3:10-13', 40
  UNION ALL SELECT 'acontecimientos-finales', 'La Tierra Nueva', 'la-tierra-nueva', 'Dios promete crear cielos nuevos y tierra nueva, donde vivirá con su pueblo, no habrá muerte ni dolor y la creación será restaurada.', 'Isaías 65:17-25; 2 Pedro 3:13; Apocalipsis 21:1-7; Apocalipsis 22:1-5', 50
) x ON x.doctrine_slug = d.slug
WHERE NOT EXISTS (
  SELECT 1 FROM belief_items i WHERE i.doctrine_id = d.id AND i.slug = x.slug
);
