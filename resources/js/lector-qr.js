import jsQR from 'jsqr';

/**
 * Lector de códigos QR con la cámara del dispositivo, para el panel web.
 *
 * Cada código leído se envía al método Livewire indicado en `metodo`, que
 * responde { exito, mensaje }. Con `conUbicacion` también se envía la
 * posición GPS (la exige el QR dinámico en las sedes con coordenadas) y con
 * `continuo` la cámara sigue abierta para leer varios carnets seguidos.
 */
function lectorQr({ metodo, conUbicacion = false, continuo = false }) {
    return {
        activo: false,
        procesando: false,
        error: null,
        resultado: null,
        lecturas: [],
        ubicacion: null,
        stream: null,
        cuadro: null,
        vigilanteGps: null,
        ultimoTexto: null,
        ultimoMomento: 0,

        async iniciar() {
            this.error = null;
            this.resultado = null;

            if (!navigator.mediaDevices?.getUserMedia) {
                this.error = 'Este navegador no permite usar la cámara. Abre el sistema con https o desde otro navegador.';

                return;
            }

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } },
                    audio: false,
                });
            } catch (error) {
                this.error = error?.name === 'NotAllowedError'
                    ? 'No diste permiso para usar la cámara. Actívalo en la configuración del navegador.'
                    : 'No se pudo abrir la cámara del dispositivo.';

                return;
            }

            const video = this.$refs.video;
            video.srcObject = this.stream;
            video.setAttribute('playsinline', 'true');
            await video.play();

            this.activo = true;

            if (conUbicacion) {
                this.seguirUbicacion();
            }

            this.escanear();
        },

        detener() {
            cancelAnimationFrame(this.cuadro);
            this.stream?.getTracks().forEach((pista) => pista.stop());
            this.stream = null;
            this.activo = false;

            if (this.vigilanteGps !== null) {
                navigator.geolocation.clearWatch(this.vigilanteGps);
                this.vigilanteGps = null;
            }
        },

        seguirUbicacion() {
            if (!navigator.geolocation) {
                return;
            }

            this.vigilanteGps = navigator.geolocation.watchPosition(
                (posicion) => (this.ubicacion = posicion.coords),
                () => (this.ubicacion = null),
                { enableHighAccuracy: true, maximumAge: 10000, timeout: 15000 },
            );
        },

        /**
         * La última posición conocida o, si todavía no llegó, la espera unos
         * segundos (el código se lee antes de que el GPS responda).
         */
        obtenerUbicacion() {
            if (this.ubicacion || !navigator.geolocation) {
                return Promise.resolve(this.ubicacion);
            }

            return new Promise((resolver) => {
                navigator.geolocation.getCurrentPosition(
                    (posicion) => resolver((this.ubicacion = posicion.coords)),
                    () => resolver(null),
                    { enableHighAccuracy: true, maximumAge: 10000, timeout: 10000 },
                );
            });
        },

        escanear() {
            const video = this.$refs.video;
            const lienzo = this.$refs.lienzo;
            const contexto = lienzo.getContext('2d', { willReadFrequently: true });

            const paso = () => {
                if (!this.activo) {
                    return;
                }

                if (video.readyState === video.HAVE_ENOUGH_DATA && !this.procesando) {
                    const ancho = Math.min(video.videoWidth, 640);
                    const alto = Math.round((video.videoHeight * ancho) / video.videoWidth);

                    lienzo.width = ancho;
                    lienzo.height = alto;
                    contexto.drawImage(video, 0, 0, ancho, alto);

                    const codigo = jsQR(contexto.getImageData(0, 0, ancho, alto).data, ancho, alto, {
                        inversionAttempts: 'dontInvert',
                    });

                    if (codigo?.data) {
                        this.leido(codigo.data);
                    }
                }

                this.cuadro = requestAnimationFrame(paso);
            };

            paso();
        },

        async leido(texto) {
            // El mismo código queda frente a la cámara varios cuadros seguidos.
            if (texto === this.ultimoTexto && Date.now() - this.ultimoMomento < 5000) {
                return;
            }

            this.ultimoTexto = texto;
            this.ultimoMomento = Date.now();
            this.procesando = true;

            try {
                const ubicacion = conUbicacion ? await this.obtenerUbicacion() : null;

                this.resultado = await this.$wire.call(metodo, texto, ubicacion?.latitude ?? null, ubicacion?.longitude ?? null);
            } catch (error) {
                this.resultado = { exito: false, mensaje: 'No se pudo enviar la lectura. Revisa tu conexión.' };
            } finally {
                this.procesando = false;
            }

            navigator.vibrate?.(this.resultado.exito ? 120 : [80, 60, 80]);

            if (continuo) {
                this.lecturas.unshift({ ...this.resultado, id: this.ultimoMomento });
                this.lecturas = this.lecturas.slice(0, 8);
            } else if (this.resultado.exito) {
                this.detener();
            }
        },

        destroy() {
            this.detener();
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('lectorQr', lectorQr);
});
