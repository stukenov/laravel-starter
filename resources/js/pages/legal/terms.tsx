import LegalDocument from '@/pages/legal/document';

const SECTIONS = [
    'scope',
    'account',
    'allowed',
    'forbidden',
    'payment',
    'liability',
    'changes',
];

export default function Terms() {
    return <LegalDocument document="terms" sections={SECTIONS} />;
}
