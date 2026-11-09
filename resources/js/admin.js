import Alpine from 'alpinejs';
import productImageUploader from './admin/product-image-uploader';
import sizeGuideEditor from './admin/size-guide-editor';

Alpine.data('productImageUploader', productImageUploader);
Alpine.data('sizeGuideEditor', sizeGuideEditor);

window.Alpine = Alpine;

Alpine.start();
