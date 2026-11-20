/**
 * Centralized Application Enums for Vue Frontend
 */

export const GenerationStatus = Object.freeze({
    QUEUED: 'queued',
    PROCESSING: 'processing',
    SUCCEEDED: 'succeeded',
    FAILED: 'failed',
});

export const GenerationQuality = Object.freeze({
    LOW: 'low',
    MEDIUM: 'medium',
    HIGH: 'high',
});

export const GenerationSize = Object.freeze({
    SQUARE: '1024x1024',
    LANDSCAPE: '1536x1024',
    PORTRAIT: '1024x1536',
});

export const CreditPurchaseTier = Object.freeze([5, 10, 25, 50, 100]);

export const PixelforgeDirectives = Object.freeze([
    { tag: '[succeed]',    color: 'emerald', desc: 'Guarantees success without random failure' },
    { tag: '[fail]',       color: 'rose',    desc: 'Fails after accept (costs $0.00)' },
    { tag: '[slow]',       color: 'amber',   desc: '60–90 seconds processing time' },
    { tag: '[duplicate]',  color: 'purple',  desc: 'Double webhook delivery for deduplication testing' },
    { tag: '[overloaded]', color: 'orange',  desc: '503 Overloaded upfront error' },
    { tag: '[blocked]',    color: 'red',     desc: '400 Content policy violation upfront' },
]);
