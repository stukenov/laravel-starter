import LegalDocument from '@/pages/legal/document';

const SECTIONS = [
    'controller',
    'collected',
    'purpose',
    'storage',
    'sharing',
    'rights',
    'contacts',
];

export default function Privacy() {
    return <LegalDocument document="privacy" sections={SECTIONS} />;
}
