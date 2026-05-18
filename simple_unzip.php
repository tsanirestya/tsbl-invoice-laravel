<?php
/**
 * Minimal Pure PHP Zip Extractor
 * Based on basic ZIP format specs
 */
class SimpleZipExtractor {
    public static function extract($zipFile, $targetDir) {
        if (!function_exists('gzinflate')) {
            throw new Exception("gzinflate function not found. Please enable zlib extension.");
        }
        if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);

        $fh = fopen($zipFile, 'rb');
        if (!$fh) return false;
        
        // Find EOCD signature backwards from the end of the file (robust against comments/extra bytes)
        fseek($fh, 0, SEEK_END);
        $fileSize = ftell($fh);
        $searchRange = min($fileSize, 1024); // standard EOCD search range is 1024 bytes
        fseek($fh, -$searchRange, SEEK_END);
        $searchData = fread($fh, $searchRange);
        
        $eocdPos = strrpos($searchData, "\x50\x4b\x05\x06");
        if ($eocdPos === false) {
            fclose($fh);
            throw new Exception("Invalid ZIP file: End of Central Directory signature not found.");
        }
        
        $endOfCentralDir = substr($searchData, $eocdPos);
        if (strlen($endOfCentralDir) < 22) {
            fclose($fh);
            throw new Exception("Invalid ZIP file: End of Central Directory record is incomplete.");
        }
        
        $data = unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_len', substr($endOfCentralDir, 4, 18));
        
        fseek($fh, $data['offset']);
        for ($i = 0; $i < $data['entries']; $i++) {
            $header = fread($fh, 46);
            if (substr($header, 0, 4) !== "\x50\x4b\x01\x02") break;
            $info = unpack('vversion/vversion_extract/vflag/vmethod/vmtime/vdate/Vcrc/Vcompressed/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', substr($header, 4));
            $filename = fread($fh, $info['filename_len']);
            fseek($fh, $info['extra_len'] + $info['comment_len'], SEEK_CUR);
            
            $currentPos = ftell($fh);
            fseek($fh, $info['offset']);
            $localHeader = fread($fh, 30);
            if (strlen($localHeader) < 30) {
                fseek($fh, $currentPos);
                continue;
            }
            $localInfo = unpack('vlen/vextra', substr($localHeader, 26));
            fseek($fh, $localInfo['len'] + $localInfo['extra'], SEEK_CUR);
            
            $targetPath = $targetDir . '/' . $filename;
            if (substr($filename, -1) === '/') {
                if (!file_exists($targetPath)) mkdir($targetPath, 0755, true);
            } else {
                $dir = dirname($targetPath);
                if (!file_exists($dir)) mkdir($dir, 0755, true);
                
                if ($info['compressed'] > 0) {
                    $content = fread($fh, $info['compressed']);
                    if ($info['method'] == 8) {
                        $content = gzinflate($content);
                    }
                    file_put_contents($targetPath, $content);
                } else {
                    file_put_contents($targetPath, '');
                }
            }
            fseek($fh, $currentPos);
        }
        fclose($fh);
        return true;
    }
}

