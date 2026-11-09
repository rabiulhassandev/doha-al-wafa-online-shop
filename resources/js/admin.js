import Alpine from 'alpinejs';
import sizeGuideEditor from './admin/size-guide-editor';

Alpine.data('sizeGuideEditor', sizeGuideEditor);

window.Alpine = Alpine;

Alpine.start();
