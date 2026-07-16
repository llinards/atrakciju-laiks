import PhotoSwipeLightbox from 'photoswipe/lightbox'
import 'photoswipe/style.css'

document.addEventListener('alpine:init', () => {
    window.Alpine.data('lightboxGallery', (images) => ({
        lightbox: null,

        init() {
            this.lightbox = new PhotoSwipeLightbox({
                dataSource: images.map((image) => ({
                    src: image.src,
                    width: image.width,
                    height: image.height,
                    alt: image.alt,
                })),
                pswpModule: () => import('photoswipe'),
            })
            this.lightbox.init()
        },

        destroy() {
            this.lightbox?.destroy()
            this.lightbox = null
        },

        open(index) {
            this.lightbox.loadAndOpen(index)
        },
    }))

    window.Alpine.data('productGallery', (images) => ({
        images,
        active: 0,
        lightbox: null,

        init() {
            this.lightbox = new PhotoSwipeLightbox({
                dataSource: this.images.map((image) => ({
                    src: image.src,
                    width: image.width,
                    height: image.height,
                    alt: image.alt,
                })),
                pswpModule: () => import('photoswipe'),
            })
            this.lightbox.on('change', () => {
                this.active = this.lightbox.pswp.currIndex
            })
            this.lightbox.init()

            this.$watch('active', () => this.scrollThumbIntoView())
        },

        scrollThumbIntoView() {
            const strip = this.$refs.thumbs
            const thumb = strip?.children[this.active]

            if (!thumb) {
                return
            }

            strip.scrollTo({
                left: thumb.offsetLeft - (strip.clientWidth - thumb.offsetWidth) / 2,
                behavior: 'smooth',
            })
        },

        destroy() {
            this.lightbox?.destroy()
            this.lightbox = null
        },

        open() {
            this.lightbox.loadAndOpen(this.active)
        },

        next() {
            this.active = (this.active + 1) % this.images.length
        },

        prev() {
            this.active = (this.active - 1 + this.images.length) % this.images.length
        },
    }))
})
