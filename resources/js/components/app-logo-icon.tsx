import type { SVGAttributes } from 'react';

/**
 * Placeholder mark — replace it when cloning. See AGENTS.md.
 *
 * Three bars widening downward: a base to build on. It is deliberately
 * plain and belongs to no product, because a starter that ships somebody
 * else's logo puts their branding into every project cloned from it.
 *
 * One colour, taken from currentColor, so the same file works on the blue
 * tile in the sidebar and on white in the auth layouts.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <rect x="7" y="3.5" width="10" height="4" rx="2" />
            <rect x="5" y="10" width="14" height="4" rx="2" />
            <rect x="3" y="16.5" width="18" height="4" rx="2" />
        </svg>
    );
}
