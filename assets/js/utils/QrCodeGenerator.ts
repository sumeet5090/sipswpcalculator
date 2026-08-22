/**
 * QrCodeGenerator.ts
 * Pure TypeScript ISO/IEC 18004 Compliant QR Code Matrix Generator.
 * Zero external dependencies. Generates valid, scannable QR Code 2D matrices
 * with Reed-Solomon Error Correction (Level M/L) and renders to HTML5 Canvas.
 */

interface QrVersionSpec {
    version: number;
    totalCodewords: number;
    ecCodewordsPerBlock: number;
    blocksG1: number;
    dataCodewordsG1: number;
    blocksG2: number;
    dataCodewordsG2: number;
    alignments: number[];
}

export class QrCodeGenerator {
    // Galois Field GF(256) Tables (Primitive Polynomial: 0x11D / 285)
    private static EXP_TABLE: number[] = new Array(512);
    private static LOG_TABLE: number[] = new Array(256);
    private static GF_INITIALIZED = false;

    // QR Code Version Specs (Level M: ~15% Error Correction)
    private static VERSION_SPECS_M: QrVersionSpec[] = [
        { version: 1, totalCodewords: 26, ecCodewordsPerBlock: 10, blocksG1: 1, dataCodewordsG1: 16, blocksG2: 0, dataCodewordsG2: 0, alignments: [] },
        { version: 2, totalCodewords: 44, ecCodewordsPerBlock: 16, blocksG1: 1, dataCodewordsG1: 28, blocksG2: 0, dataCodewordsG2: 0, alignments: [6, 18] },
        { version: 3, totalCodewords: 70, ecCodewordsPerBlock: 26, blocksG1: 1, dataCodewordsG1: 44, blocksG2: 0, dataCodewordsG2: 0, alignments: [6, 22] },
        { version: 4, totalCodewords: 100, ecCodewordsPerBlock: 18, blocksG1: 2, dataCodewordsG1: 32, blocksG2: 0, dataCodewordsG2: 0, alignments: [6, 26] },
        { version: 5, totalCodewords: 134, ecCodewordsPerBlock: 24, blocksG1: 2, dataCodewordsG1: 43, blocksG2: 0, dataCodewordsG2: 0, alignments: [6, 30] },
        { version: 6, totalCodewords: 172, ecCodewordsPerBlock: 16, blocksG1: 4, dataCodewordsG1: 27, blocksG2: 0, dataCodewordsG2: 0, alignments: [6, 34] },
        { version: 7, totalCodewords: 196, ecCodewordsPerBlock: 18, blocksG1: 4, dataCodewordsG1: 31, blocksG2: 0, dataCodewordsG2: 0, alignments: [6, 22, 38] },
        { version: 8, totalCodewords: 242, ecCodewordsPerBlock: 22, blocksG1: 2, dataCodewordsG1: 38, blocksG2: 2, dataCodewordsG2: 39, alignments: [6, 24, 42] },
        { version: 9, totalCodewords: 292, ecCodewordsPerBlock: 22, blocksG1: 3, dataCodewordsG1: 36, blocksG2: 2, dataCodewordsG2: 37, alignments: [6, 26, 46] },
        { version: 10, totalCodewords: 346, ecCodewordsPerBlock: 26, blocksG1: 4, dataCodewordsG1: 43, blocksG2: 1, dataCodewordsG2: 44, alignments: [6, 28, 50] }
    ];

    private static initGF(): void {
        if (this.GF_INITIALIZED) return;
        let x = 1;
        for (let i = 0; i < 255; i++) {
            this.EXP_TABLE[i] = x;
            this.LOG_TABLE[x] = i;
            x = (x << 1);
            if (x & 0x100) {
                x ^= 0x11D;
            }
        }
        for (let i = 255; i < 512; i++) {
            this.EXP_TABLE[i] = this.EXP_TABLE[i - 255];
        }
        this.GF_INITIALIZED = true;
    }

    private static gfMul(x: number, y: number): number {
        if (x === 0 || y === 0) return 0;
        return this.EXP_TABLE[this.LOG_TABLE[x] + this.LOG_TABLE[y]];
    }

    /**
     * Generate Reed-Solomon generator polynomial for given error correction count.
     */
    private static rsGeneratorPolynomial(ecCount: number): number[] {
        let poly = [1];
        for (let i = 0; i < ecCount; i++) {
            const next = [1, this.EXP_TABLE[i]];
            const res: number[] = new Array(poly.length + 1).fill(0);
            for (let j = 0; j < poly.length; j++) {
                res[j] ^= this.gfMul(poly[j], next[0]);
                res[j + 1] ^= this.gfMul(poly[j], next[1]);
            }
            poly = res;
        }
        return poly;
    }

    /**
     * Compute Reed-Solomon error correction codewords for a data block.
     */
    private static rsEncodeBlock(data: number[], ecCount: number): number[] {
        const generator = this.rsGeneratorPolynomial(ecCount);
        const result = new Array(ecCount).fill(0);

        for (let i = 0; i < data.length; i++) {
            const factor = data[i] ^ result[0];
            for (let j = 0; j < ecCount - 1; j++) {
                result[j] = result[j + 1] ^ this.gfMul(generator[j + 1], factor);
            }
            result[ecCount - 1] = this.gfMul(generator[ecCount], factor);
        }

        return result;
    }

    /**
     * Select appropriate QR Version based on text payload length.
     */
    private static selectVersion(byteLength: number): QrVersionSpec {
        for (const spec of this.VERSION_SPECS_M) {
            const maxData = (spec.blocksG1 * spec.dataCodewordsG1) + (spec.blocksG2 * spec.dataCodewordsG2);
            // 8-bit mode header: 4 bits mode + 8/16 bits char count = 2 or 3 bytes overhead
            const maxTextBytes = maxData - (spec.version <= 9 ? 2 : 3);
            if (byteLength <= maxTextBytes) {
                return spec;
            }
        }
        return this.VERSION_SPECS_M[this.VERSION_SPECS_M.length - 1];
    }

    /**
     * Encodes text into QR BitStream with Byte Mode header, length, and padding.
     */
    private static encodeDataCodewords(text: string, spec: QrVersionSpec): number[] {
        const utf8Bytes: number[] = [];
        for (let i = 0; i < text.length; i++) {
            let code = text.charCodeAt(i);
            if (code < 0x80) {
                utf8Bytes.push(code);
            } else if (code < 0x800) {
                utf8Bytes.push(0xc0 | (code >> 6), 0x80 | (code & 0x3f));
            } else if (code < 0xd800 || code >= 0xe000) {
                utf8Bytes.push(0xe0 | (code >> 12), 0x80 | ((code >> 6) & 0x3f), 0x80 | (code & 0x3f));
            } else {
                i++;
                const nextCode = text.charCodeAt(i);
                const surrogate = 0x10000 + (((code & 0x3ff) << 10) | (nextCode & 0x3ff));
                utf8Bytes.push(
                    0xf0 | (surrogate >> 18),
                    0x80 | ((surrogate >> 12) & 0x3f),
                    0x80 | ((surrogate >> 6) & 0x3f),
                    0x80 | (surrogate & 0x3f)
                );
            }
        }

        const totalDataCodewords = (spec.blocksG1 * spec.dataCodewordsG1) + (spec.blocksG2 * spec.dataCodewordsG2);
        const bitBuffer: number[] = [];

        const writeBits = (val: number, numBits: number) => {
            for (let i = numBits - 1; i >= 0; i--) {
                bitBuffer.push((val >> i) & 1);
            }
        };

        // 1. Mode Indicator: 0100 (8-bit Byte Mode)
        writeBits(0b0100, 4);

        // 2. Character Count Indicator (8 bits for V1-9, 16 bits for V10+)
        const countBits = spec.version <= 9 ? 8 : 16;
        writeBits(utf8Bytes.length, countBits);

        // 3. UTF-8 Data Bytes
        for (const byte of utf8Bytes) {
            writeBits(byte, 8);
        }

        // 4. Terminator (up to 4 zeroes)
        const totalBitsCapacity = totalDataCodewords * 8;
        const remainingBits = totalBitsCapacity - bitBuffer.length;
        const terminatorLen = Math.min(Math.max(remainingBits, 0), 4);
        writeBits(0, terminatorLen);

        // 5. Pad to Byte Boundary
        while (bitBuffer.length % 8 !== 0) {
            bitBuffer.push(0);
        }

        // Convert bit buffer to bytes
        const dataBytes: number[] = [];
        for (let i = 0; i < bitBuffer.length; i += 8) {
            let byte = 0;
            for (let b = 0; b < 8; b++) {
                byte = (byte << 1) | bitBuffer[i + b];
            }
            dataBytes.push(byte);
        }

        // 6. Pad with Alternating 0xEC (236) and 0x11 (17) until total data capacity reached
        const padBytes = [0xec, 0x11];
        let padIndex = 0;
        while (dataBytes.length < totalDataCodewords) {
            dataBytes.push(padBytes[padIndex % 2]);
            padIndex++;
        }

        return dataBytes;
    }

    /**
     * Generate complete interleaved data and error correction codewords.
     */
    private static generateInterleavedCodewords(dataBytes: number[], spec: QrVersionSpec): number[] {
        const totalBlocks = spec.blocksG1 + spec.blocksG2;
        const dataBlocks: number[][] = [];
        const ecBlocks: number[][] = [];

        let offset = 0;
        for (let i = 0; i < spec.blocksG1; i++) {
            const block = dataBytes.slice(offset, offset + spec.dataCodewordsG1);
            dataBlocks.push(block);
            ecBlocks.push(this.rsEncodeBlock(block, spec.ecCodewordsPerBlock));
            offset += spec.dataCodewordsG1;
        }
        for (let i = 0; i < spec.blocksG2; i++) {
            const block = dataBytes.slice(offset, offset + spec.dataCodewordsG2);
            dataBlocks.push(block);
            ecBlocks.push(this.rsEncodeBlock(block, spec.ecCodewordsPerBlock));
            offset += spec.dataCodewordsG2;
        }

        const interleaved: number[] = [];

        // Interleave Data Codewords
        const maxDataLen = Math.max(spec.dataCodewordsG1, spec.dataCodewordsG2 || 0);
        for (let col = 0; col < maxDataLen; col++) {
            for (let b = 0; b < totalBlocks; b++) {
                if (col < dataBlocks[b].length) {
                    interleaved.push(dataBlocks[b][col]);
                }
            }
        }

        // Interleave Error Correction Codewords
        for (let col = 0; col < spec.ecCodewordsPerBlock; col++) {
            for (let b = 0; b < totalBlocks; b++) {
                interleaved.push(ecBlocks[b][col]);
            }
        }

        return interleaved;
    }

    /**
     * Create 2D QR matrix with all function patterns and interleaved data.
     */
    public static generateMatrix(text: string): boolean[][] {
        this.initGF();
        const spec = this.selectVersion(text.length);
        const dataBytes = this.encodeDataCodewords(text, spec);
        const codewords = this.generateInterleavedCodewords(dataBytes, spec);

        const size = spec.version * 4 + 17;
        const matrix: (boolean | null)[][] = Array.from({ length: size }, () => new Array(size).fill(null));
        const isFunction: boolean[][] = Array.from({ length: size }, () => new Array(size).fill(false));

        const setModule = (r: number, c: number, val: boolean, isFunc: boolean = true) => {
            if (r >= 0 && r < size && c >= 0 && c < size) {
                matrix[r][c] = val;
                if (isFunc) isFunction[r][c] = true;
            }
        };

        // 1. Finder Patterns (7x7) + Separators
        const addFinder = (row: number, col: number) => {
            for (let r = -1; r <= 7; r++) {
                for (let c = -1; c <= 7; c++) {
                    const absR = row + r;
                    const absC = col + c;
                    if (r >= 0 && r <= 6 && c >= 0 && c <= 6) {
                        const isBlack = (r === 0 || r === 6 || c === 0 || c === 6 || (r >= 2 && r <= 4 && c >= 2 && c <= 4));
                        setModule(absR, absC, isBlack, true);
                    } else {
                        setModule(absR, absC, false, true); // 1-module white separator
                    }
                }
            }
        };

        addFinder(0, 0);
        addFinder(0, size - 7);
        addFinder(size - 7, 0);

        // 2. Alignment Patterns (5x5) for Version 2+
        const alignments = spec.alignments;
        for (const ar of alignments) {
            for (const ac of alignments) {
                // Skip alignments overlapping finder patterns
                if ((ar === 6 && ac === 6) || (ar === 6 && ac === size - 7) || (ar === size - 7 && ac === 6)) {
                    continue;
                }
                for (let r = -2; r <= 2; r++) {
                    for (let c = -2; c <= 2; c++) {
                        const isBlack = (Math.abs(r) === 2 || Math.abs(c) === 2 || (r === 0 && c === 0));
                        setModule(ar + r, ac + c, isBlack, true);
                    }
                }
            }
        }

        // 3. Timing Patterns
        for (let i = 8; i < size - 8; i++) {
            const isBlack = (i % 2 === 0);
            if (matrix[6][i] === null) setModule(6, i, isBlack, true);
            if (matrix[i][6] === null) setModule(i, 6, isBlack, true);
        }

        // 4. Dark Module
        setModule(4 * spec.version + 9, 8, true, true);

        // 5. Reserve Format Information areas
        for (let i = 0; i < 9; i++) {
            if (matrix[8][i] === null) isFunction[8][i] = true;
            if (matrix[i][8] === null) isFunction[i][8] = true;
        }
        for (let i = size - 8; i < size; i++) {
            if (matrix[8][i] === null) isFunction[8][i] = true;
            if (matrix[i][8] === null) isFunction[i][8] = true;
        }

        // 6. Data Placement in 2-column zig-zag right-to-left
        let bitIndex = 0;
        const totalBits = codewords.length * 8;
        let upward = true;

        for (let right = size - 1; right > 0; right -= 2) {
            if (right === 6) right--; // Skip vertical timing line
            const cols = [right, right - 1];

            const rows = upward
                ? Array.from({ length: size }, (_, idx) => size - 1 - idx)
                : Array.from({ length: size }, (_, idx) => idx);

            for (const row of rows) {
                for (const col of cols) {
                    if (!isFunction[row][col]) {
                        let bit = 0;
                        if (bitIndex < totalBits) {
                            const byteIdx = Math.floor(bitIndex / 8);
                            const bitPos = 7 - (bitIndex % 8);
                            bit = (codewords[byteIdx] >> bitPos) & 1;
                            bitIndex++;
                        }

                        // Apply Mask 0: (row + col) % 2 === 0
                        const mask = (row + col) % 2 === 0;
                        matrix[row][col] = (bit === 1) ? !mask : mask;
                    }
                }
            }
            upward = !upward;
        }

        // 7. Write Format Information (Level M, Mask 0 = 0x5412)
        const formatBits = 0x5412; // Precomputed BCH(15,5) for Level M + Mask 0
        const getFormatBit = (idx: number) => ((formatBits >> idx) & 1) === 1;

        // Around Top-Left Finder
        const tlCoords = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8],
            [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8]
        ];
        for (let i = 0; i < 15; i++) {
            matrix[tlCoords[i][0]][tlCoords[i][1]] = getFormatBit(14 - i);
        }

        // Around Top-Right and Bottom-Left Finders
        for (let i = 0; i < 8; i++) {
            matrix[8][size - 1 - i] = getFormatBit(14 - i);
        }
        for (let i = 0; i < 7; i++) {
            matrix[size - 7 + i][8] = getFormatBit(6 - i);
        }

        return matrix.map(row => row.map(cell => cell === true));
    }

    /**
     * Renders QR Code matrix to HTML5 Canvas with quiet zone and crisp subpixel alignment.
     */
    public static renderToCanvas(
        canvas: HTMLCanvasElement,
        text: string,
        darkColor: string = '#0f172a',
        lightColor: string = '#ffffff'
    ): void {
        const matrix = this.generateMatrix(text);
        const matrixSize = matrix.length;
        const quietZone = 4; // Standard 4-module quiet zone
        const totalSize = matrixSize + quietZone * 2;

        const targetPx = 200;
        const cellSize = Math.floor(targetPx / totalSize);
        const actualPx = cellSize * totalSize;

        canvas.width = actualPx;
        canvas.height = actualPx;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        // Background
        ctx.fillStyle = lightColor;
        ctx.fillRect(0, 0, actualPx, actualPx);

        // Modules
        ctx.fillStyle = darkColor;
        for (let r = 0; r < matrixSize; r++) {
            for (let c = 0; c < matrixSize; c++) {
                if (matrix[r][c]) {
                    ctx.fillRect((c + quietZone) * cellSize, (r + quietZone) * cellSize, cellSize, cellSize);
                }
            }
        }
    }
}
