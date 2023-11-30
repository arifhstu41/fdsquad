<?php

namespace App\Repositories\BrandRepository;

use App\Models\Brand;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Model;

class BrandRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Brand::class;
    }

    public function brandsList(array $array = [])
    {
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

		return $this->model()
			->with([
				'shop.translation' => fn($q) => $q->select('id', 'shop_id', 'locale', 'title')
					->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
			])
            ->updatedDate($this->updatedDate)
            ->filter($array)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Get brands with pagination
     */
    public function brandsPaginate(array $filter = [])
    {
		$locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        return $this->model()
			->with([
				'shop.translation' => fn($q) => $q->select('id', 'shop_id', 'locale', 'title')
                    ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale))
			])
            ->withCount([
                'products' => fn($q) => $q
					->whereHas('shop', fn($q) => $q->whereNull('deleted_at'))
                    ->whereHas('stocks', fn($q) => $q->where('quantity', '>', 0))
            ])
            ->filter($filter)
            ->updatedDate($this->updatedDate)
            ->paginate(data_get($filter, 'perPage', 10));
    }

    /**
     * Get one brands by Identification number
     */
    public function brandDetails(int $id)
    {
        return $this->model()->find($id);
    }

    /**
     * @param string $slug
     * @return Model|null
     */
    public function brandDetailsBySlug(string $slug): Model|null
    {
        return $this->model()->where('slug', $slug)->first();

    }

    public function brandsSearch(array $filter = [])
    {
        return $this->model()
            ->withCount('products')
            ->when(data_get($filter, 'search'), fn($q, $search) => $q->where('title', 'LIKE', "%$search%"))
            ->when(isset($filter['active']), fn($q) => $q->whereActive($filter['active']))
            ->orderBy(data_get($filter,'column','id'), data_get($filter,'sort','desc'))
            ->paginate(data_get($filter, 'perPage', 10));
    }
}
