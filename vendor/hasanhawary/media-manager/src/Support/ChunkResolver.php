<?php

namespace HasanHawary\MediaManager\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkResolver
{
    /**
     * @param $data
     * @param $path
     * @param $is_final
     * @return false|string
     */
    public function upload($data, $path, $is_final): false|string
    {
        // Get the file name from the uploaded file data
        $fileName = pathinfo($data['file_name'], PATHINFO_FILENAME);
        $chunkDir = "chunks/" . (auth()->id() ?? 1) . "/$fileName";

        // Ensure the chunks directory is new dir for first chunk
        when(Storage::exists($chunkDir) && (int)$data['chunk_number'] === 1, fn() => Storage::deleteDirectory($chunkDir));

        // Create the chunks directory if it does not exist
        when(!Storage::exists($chunkDir), fn() => Storage::makeDirectory($chunkDir));

        // Save the chunk
        $file = Storage::putFileAs($chunkDir, $data['chunk_file'], $data['chunk_number']);

        if ($is_final) {
            return $this->combineChunks($chunkDir, $path, $data['file_name']);
        }

        return $file;
    }

    /**
     * @param $chunkDir
     * @param $path
     * @param $fileName
     * @return string
     */
    public function combineChunks($chunkDir, $path, $fileName): string
    {
        // Create the final directory if it does not exist
        when(!Storage::exists($path), fn() => Storage::makeDirectory($path));

        // Generate a unique file name for the final file and its path
        $finalPath = "$path/(" . Str::limit(strrev(time()), 4, '') . ")_$fileName";
        $finalFile = Storage::path($finalPath);

        // Merge all chunks into the final file
        $finalFileOpen = fopen($finalFile, 'ab');
        for ($i = 1, $iMax = count(Storage::files($chunkDir)); $i <= $iMax; $i++) {
            fwrite($finalFileOpen, Storage::get("$chunkDir/$i"));
            Storage::delete("$chunkDir/$i"); // delete chunk after appending
        }

        // Close the final file and delete the chunks directory
        fclose($finalFileOpen);
        Storage::deleteDirectory($chunkDir);

        return $finalPath;
    }
}
