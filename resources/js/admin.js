import Alpine from 'alpinejs';
import productImageUploader from './admin/product-image-uploader';
import sizeGuideEditor from './admin/size-guide-editor';
import variantPicker from './admin/variant-picker';

Alpine.data('productImageUploader', productImageUploader);
Alpine.data('sizeGuideEditor', sizeGuideEditor);
Alpine.data('variantPicker', variantPicker);

window.Alpine = Alpine;

Alpine.start();
