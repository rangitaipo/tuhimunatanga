const wordlistSize = 7776;
const maximumMultiplicity = 2;

function calculate( words ) {
	const ideal = words * Math.log2( wordlistSize );
	const conservative = words * Math.log2( wordlistSize / maximumMultiplicity );
	const effective = Math.min( conservative, 256 );
	const quantum = effective / 2;
	return { ideal, conservative, effective, quantum };
}

const seven = calculate( 7 );
const twentyTwo = calculate( 22 );

if ( Math.abs( seven.conservative - 83.47368752524046 ) > 1e-9 ) {
	throw new Error( 'Seven-word entropy calculation changed unexpectedly.' );
}
if ( Math.abs( twentyTwo.conservative - 262.3458750793272 ) > 1e-9 ) {
	throw new Error( 'Twenty-two-word entropy calculation changed unexpectedly.' );
}
if ( twentyTwo.effective !== 256 || twentyTwo.quantum !== 128 ) {
	throw new Error( 'Twenty-two words do not reach the configured post-quantum target.' );
}

console.log( 'Entropy calculations passed: 22 words -> 262.35-bit conservative entropy, 256-bit effective ceiling, 128-bit simplified quantum-search estimate.' );
