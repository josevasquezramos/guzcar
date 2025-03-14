<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trabajo_articulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('despacho_id')
                ->nullable()
                ->constrained('despachos')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->date('fecha');
            $table->time('hora');
            $table->foreignId('trabajo_id')
                ->nullable()
                ->constrained('trabajos')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('articulo_id')
                ->constrained('articulos')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->decimal('precio')->unsigned();
            $table->decimal('cantidad')->unsigned()->default(1);
            $table->foreignId('tecnico_id')
                ->constrained('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->foreignId('responsable_id')
                ->constrained('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->enum('movimiento', ['cerrado', 'abierto'])->default('cerrado');
            $table->text('observacion')->nullable();
            $table->boolean('confirmado')->default(false);
            $table->timestamps();
        });

        DB::unprepared("
            CREATE TRIGGER after_trabajo_articulos_insert
            AFTER INSERT ON trabajo_articulos
            FOR EACH ROW
            BEGIN
                DECLARE stock_diff INT;
                DECLARE abiertos_diff DECIMAL(10,2);
                DECLARE new_stock INT;
                DECLARE new_abiertos DECIMAL(10,2);

                IF NEW.movimiento = 'cerrado' THEN
                    -- Reducir el stock en el valor redondeado hacia arriba
                    SET stock_diff = CEIL(NEW.cantidad);
                    -- Aumentar abiertos en el excedente
                    SET abiertos_diff = stock_diff - NEW.cantidad;

                    -- Calcular el nuevo stock y abiertos
                    SET new_stock = GREATEST((SELECT stock FROM articulos WHERE id = NEW.articulo_id) - stock_diff, 0);
                    SET new_abiertos = GREATEST((SELECT abiertos FROM articulos WHERE id = NEW.articulo_id) + abiertos_diff, 0);

                    -- Actualizar la tabla articulos
                    UPDATE articulos
                    SET stock = new_stock,
                        abiertos = new_abiertos
                    WHERE id = NEW.articulo_id;
                ELSE
                    -- Reducir abiertos directamente
                    SET new_abiertos = GREATEST((SELECT abiertos FROM articulos WHERE id = NEW.articulo_id) - NEW.cantidad, 0);

                    -- Actualizar la tabla articulos
                    UPDATE articulos
                    SET abiertos = new_abiertos
                    WHERE id = NEW.articulo_id;
                END IF;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER after_trabajo_articulos_update
            AFTER UPDATE ON trabajo_articulos
            FOR EACH ROW
            BEGIN
                DECLARE stock_diff INT;
                DECLARE abiertos_diff DECIMAL(10,2);
                DECLARE new_stock INT;
                DECLARE new_abiertos DECIMAL(10,2);

                -- Revertir el efecto del registro antiguo
                IF OLD.movimiento = 'cerrado' THEN
                    SET stock_diff = CEIL(OLD.cantidad);
                    SET abiertos_diff = stock_diff - OLD.cantidad;

                    -- Calcular el nuevo stock y abiertos
                    SET new_stock = GREATEST((SELECT stock FROM articulos WHERE id = OLD.articulo_id) + stock_diff, 0);
                    SET new_abiertos = GREATEST((SELECT abiertos FROM articulos WHERE id = OLD.articulo_id) - abiertos_diff, 0);

                    -- Actualizar la tabla articulos
                    UPDATE articulos
                    SET stock = new_stock,
                        abiertos = new_abiertos
                    WHERE id = OLD.articulo_id;
                ELSE
                    -- Revertir la reducción de abiertos
                    SET new_abiertos = GREATEST((SELECT abiertos FROM articulos WHERE id = OLD.articulo_id) + OLD.cantidad, 0);

                    -- Actualizar la tabla articulos
                    UPDATE articulos
                    SET abiertos = new_abiertos
                    WHERE id = OLD.articulo_id;
                END IF;

                -- Aplicar el efecto del nuevo registro
                IF NEW.movimiento = 'cerrado' THEN
                    SET stock_diff = CEIL(NEW.cantidad);
                    SET abiertos_diff = stock_diff - NEW.cantidad;

                    -- Calcular el nuevo stock y abiertos
                    SET new_stock = GREATEST((SELECT stock FROM articulos WHERE id = NEW.articulo_id) - stock_diff, 0);
                    SET new_abiertos = GREATEST((SELECT abiertos FROM articulos WHERE id = NEW.articulo_id) + abiertos_diff, 0);

                    -- Actualizar la tabla articulos
                    UPDATE articulos
                    SET stock = new_stock,
                        abiertos = new_abiertos
                    WHERE id = NEW.articulo_id;
                ELSE
                    -- Reducir abiertos directamente
                    SET new_abiertos = GREATEST((SELECT abiertos FROM articulos WHERE id = NEW.articulo_id) - NEW.cantidad, 0);

                    -- Actualizar la tabla articulos
                    UPDATE articulos
                    SET abiertos = new_abiertos
                    WHERE id = NEW.articulo_id;
                END IF;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER after_trabajo_articulos_delete
            AFTER DELETE ON trabajo_articulos
            FOR EACH ROW
            BEGIN
                DECLARE stock_diff INT;
                DECLARE abiertos_diff DECIMAL(10,2);
                DECLARE new_stock INT;
                DECLARE new_abiertos DECIMAL(10,2);

                IF OLD.movimiento = 'cerrado' THEN
                    SET stock_diff = CEIL(OLD.cantidad);
                    SET abiertos_diff = stock_diff - OLD.cantidad;

                    -- Calcular el nuevo stock y abiertos
                    SET new_stock = GREATEST((SELECT stock FROM articulos WHERE id = OLD.articulo_id) + stock_diff, 0);
                    SET new_abiertos = GREATEST((SELECT abiertos FROM articulos WHERE id = OLD.articulo_id) - abiertos_diff, 0);

                    -- Actualizar la tabla articulos
                    UPDATE articulos
                    SET stock = new_stock,
                        abiertos = new_abiertos
                    WHERE id = OLD.articulo_id;
                ELSE
                    -- Revertir la reducción de abiertos
                    SET new_abiertos = GREATEST((SELECT abiertos FROM articulos WHERE id = OLD.articulo_id) + OLD.cantidad, 0);

                    -- Actualizar la tabla articulos
                    UPDATE articulos
                    SET abiertos = new_abiertos
                    WHERE id = OLD.articulo_id;
                END IF;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER after_insert_trabajo_articulo
            AFTER INSERT ON trabajo_articulos
            FOR EACH ROW
            BEGIN
                DECLARE total_articulos DECIMAL(10, 2);
                DECLARE total_servicios DECIMAL(10, 2);

                -- Calcular el total de artículos para el trabajo
                SELECT COALESCE(SUM(precio * cantidad), 0) INTO total_articulos
                FROM trabajo_articulos
                WHERE trabajo_id = NEW.trabajo_id;

                -- Calcular el total de servicios para el trabajo
                SELECT COALESCE(SUM(precio * cantidad), 0) INTO total_servicios
                FROM trabajo_servicios
                WHERE trabajo_id = NEW.trabajo_id;

                -- Actualizar el campo `importe` en la tabla `trabajos`
                UPDATE trabajos
                SET importe = total_articulos + total_servicios
                WHERE id = NEW.trabajo_id;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER after_update_trabajo_articulo
            AFTER UPDATE ON trabajo_articulos
            FOR EACH ROW
            BEGIN
                DECLARE total_articulos DECIMAL(10, 2);
                DECLARE total_servicios DECIMAL(10, 2);

                -- Calcular el total de artículos para el trabajo
                SELECT COALESCE(SUM(precio * cantidad), 0) INTO total_articulos
                FROM trabajo_articulos
                WHERE trabajo_id = NEW.trabajo_id;

                -- Calcular el total de servicios para el trabajo
                SELECT COALESCE(SUM(precio * cantidad), 0) INTO total_servicios
                FROM trabajo_servicios
                WHERE trabajo_id = NEW.trabajo_id;

                -- Actualizar el campo `importe` en la tabla `trabajos`
                UPDATE trabajos
                SET importe = total_articulos + total_servicios
                WHERE id = NEW.trabajo_id;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER after_delete_trabajo_articulo
            AFTER DELETE ON trabajo_articulos
            FOR EACH ROW
            BEGIN
                DECLARE total_articulos DECIMAL(10, 2);
                DECLARE total_servicios DECIMAL(10, 2);

                -- Calcular el total de artículos para el trabajo
                SELECT COALESCE(SUM(precio * cantidad), 0) INTO total_articulos
                FROM trabajo_articulos
                WHERE trabajo_id = OLD.trabajo_id;

                -- Calcular el total de servicios para el trabajo
                SELECT COALESCE(SUM(precio * cantidad), 0) INTO total_servicios
                FROM trabajo_servicios
                WHERE trabajo_id = OLD.trabajo_id;

                -- Actualizar el campo `importe` en la tabla `trabajos`
                UPDATE trabajos
                SET importe = total_articulos + total_servicios
                WHERE id = OLD.trabajo_id;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER after_insert_trabajo_servicio
            AFTER INSERT ON trabajo_servicios
            FOR EACH ROW
            BEGIN
                DECLARE total_articulos DECIMAL(10, 2);
                DECLARE total_servicios DECIMAL(10, 2);

                -- Calcular el total de servicios para el trabajo
                SELECT COALESCE(SUM(precio * cantidad), 0) INTO total_servicios
                FROM trabajo_servicios
                WHERE trabajo_id = NEW.trabajo_id;

                -- Calcular el total de artículos para el trabajo
                SELECT COALESCE(SUM(precio * cantidad), 0) INTO total_articulos
                FROM trabajo_articulos
                WHERE trabajo_id = NEW.trabajo_id;

                -- Actualizar el campo `importe` en la tabla `trabajos`
                UPDATE trabajos
                SET importe = total_articulos + total_servicios
                WHERE id = NEW.trabajo_id;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER after_update_trabajo_servicio
            AFTER UPDATE ON trabajo_servicios
            FOR EACH ROW
            BEGIN
                DECLARE total_articulos DECIMAL(10, 2);
                DECLARE total_servicios DECIMAL(10, 2);

                -- Calcular el total de servicios para el trabajo
                SELECT COALESCE(SUM(precio * cantidad), 0) INTO total_servicios
                FROM trabajo_servicios
                WHERE trabajo_id = NEW.trabajo_id;

                -- Calcular el total de artículos para el trabajo
                SELECT COALESCE(SUM(precio * cantidad), 0) INTO total_articulos
                FROM trabajo_articulos
                WHERE trabajo_id = NEW.trabajo_id;

                -- Actualizar el campo `importe` en la tabla `trabajos`
                UPDATE trabajos
                SET importe = total_articulos + total_servicios
                WHERE id = NEW.trabajo_id;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER after_delete_trabajo_servicio
            AFTER DELETE ON trabajo_servicios
            FOR EACH ROW
            BEGIN
                DECLARE total_articulos DECIMAL(10, 2);
                DECLARE total_servicios DECIMAL(10, 2);

                -- Calcular el total de servicios para el trabajo
                SELECT COALESCE(SUM(precio * cantidad), 0) INTO total_servicios
                FROM trabajo_servicios
                WHERE trabajo_id = OLD.trabajo_id;

                -- Calcular el total de artículos para el trabajo
                SELECT COALESCE(SUM(precio * cantidad), 0) INTO total_articulos
                FROM trabajo_articulos
                WHERE trabajo_id = OLD.trabajo_id;

                -- Actualizar el campo `importe` en la tabla `trabajos`
                UPDATE trabajos
                SET importe = total_articulos + total_servicios
                WHERE id = OLD.trabajo_id;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER before_insert_trabajo_articulo
            BEFORE INSERT ON trabajo_articulos
            FOR EACH ROW
            BEGIN
                DECLARE despacho_fecha DATE;
                DECLARE despacho_hora TIME;
                DECLARE despacho_trabajo_id INT;
                DECLARE despacho_tecnico_id INT;
                DECLARE despacho_responsable_id INT;

                -- Obtener los valores del despacho si está relacionado
                IF NEW.despacho_id IS NOT NULL THEN
                    SELECT fecha, hora, trabajo_id, tecnico_id, responsable_id
                    INTO despacho_fecha, despacho_hora, despacho_trabajo_id, despacho_tecnico_id, despacho_responsable_id
                    FROM despachos
                    WHERE id = NEW.despacho_id;

                    -- Establecer los valores del despacho si los campos en trabajo_articulos son NULL
                    IF NEW.fecha IS NULL THEN
                        SET NEW.fecha = despacho_fecha;
                    END IF;

                    IF NEW.hora IS NULL THEN
                        SET NEW.hora = despacho_hora;
                    END IF;

                    IF NEW.trabajo_id IS NULL THEN
                        SET NEW.trabajo_id = despacho_trabajo_id;
                    END IF;

                    IF NEW.tecnico_id IS NULL THEN
                        SET NEW.tecnico_id = despacho_tecnico_id;
                    END IF;

                    IF NEW.responsable_id IS NULL THEN
                        SET NEW.responsable_id = despacho_responsable_id;
                    END IF;
                END IF;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER after_update_despacho
            AFTER UPDATE ON despachos
            FOR EACH ROW
            BEGIN
                -- Actualizar los campos en trabajo_articulos si el despacho_id coincide
                UPDATE trabajo_articulos
                SET fecha = NEW.fecha,
                    hora = NEW.hora,
                    trabajo_id = NEW.trabajo_id,
                    tecnico_id = NEW.tecnico_id,
                    responsable_id = NEW.responsable_id
                WHERE despacho_id = NEW.id;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajo_articulos');
        
        DB::unprepared('DROP TRIGGER IF EXISTS before_insert_trabajo_articulo;');
        DB::unprepared('DROP TRIGGER IF EXISTS after_update_despacho;');

        DB::unprepared("DROP TRIGGER IF EXISTS after_trabajo_articulos_insert");
        DB::unprepared("DROP TRIGGER IF EXISTS after_trabajo_articulos_update");
        DB::unprepared("DROP TRIGGER IF EXISTS after_trabajo_articulos_delete");

        DB::unprepared("DROP TRIGGER IF EXISTS after_insert_trabajo_articulo");
        DB::unprepared("DROP TRIGGER IF EXISTS after_update_trabajo_articulo");
        DB::unprepared("DROP TRIGGER IF EXISTS after_delete_trabajo_articulo");

        DB::unprepared("DROP TRIGGER IF EXISTS after_insert_trabajo_servicio");
        DB::unprepared("DROP TRIGGER IF EXISTS after_update_trabajo_servicio");
        DB::unprepared("DROP TRIGGER IF EXISTS after_delete_trabajo_servicio");
    }
};
