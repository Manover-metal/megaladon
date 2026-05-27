<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * App\Models\Advert
 *
 * @property int $id
 * @property int $user_id
 * @property int $city_id
 * @property string $title
 * @property string|null $description
 * @property string $price
 * @property string|null $additional_phone
 * @property int $category_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AdvertCategory|null $category
 * @property-read \App\Models\Chat|null $chatable
 * @property-read \App\Models\City|null $city
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MediaFiles> $media
 * @property-read int|null $media_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Advert newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Advert newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Advert query()
 * @method static \Illuminate\Database\Eloquent\Builder|Advert whereAdditionalPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Advert whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Advert whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Advert whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Advert whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Advert whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Advert wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Advert whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Advert whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Advert whereUserId($value)
 */
	class Advert extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\AdvertCategory
 *
 * @property int $id
 * @property string $name
 * @property int $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AdvertCategory> $child
 * @property-read int|null $child_count
 * @method static \Illuminate\Database\Eloquent\Builder|AdvertCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdvertCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdvertCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|AdvertCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdvertCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdvertCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdvertCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdvertCategory whereUpdatedAt($value)
 */
	class AdvertCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Chat
 *
 * @property int $id
 * @property string $chatable_type
 * @property int $chatable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $chatable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat query()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereChatableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereChatableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUpdatedAt($value)
 */
	class Chat extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ChatMessage
 *
 * @property int $id
 * @property int $chat_id
 * @property int $user_id
 * @property string|null $message
 * @property string|null $file_url
 * @property int $is_readed
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereFileUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereIsReaded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereUserId($value)
 */
	class ChatMessage extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ChatUser
 *
 * @property int $id
 * @property int $chat_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ChatUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatUser whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatUser whereUserId($value)
 */
	class ChatUser extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\City
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|City newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|City newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|City query()
 * @method static \Illuminate\Database\Eloquent\Builder|City whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|City whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|City whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|City whereUpdatedAt($value)
 */
	class City extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Comment
 *
 * @property int $id
 * @property string $commentable_type
 * @property int $commentable_id
 * @property string $comment
 * @property int $user_id
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Comment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Comment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Comment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Comment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Comment whereCommentableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Comment whereCommentableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Comment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Comment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Comment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Comment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Comment whereUserId($value)
 */
	class Comment extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\CompanyType
 *
 * @property int $id
 * @property string $title
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyType query()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyType whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyType whereUpdatedAt($value)
 */
	class CompanyType extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Executor
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $bin
 * @property string $lat
 * @property string $lon
 * @property string $full_address
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rating> $rating
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read int|null $rating_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderCategory> $services
 * @property-read int|null $services_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Executor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Executor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Executor query()
 * @method static \Illuminate\Database\Eloquent\Builder|Executor whereBin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Executor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Executor whereFullAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Executor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Executor whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Executor whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Executor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Executor whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Executor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Executor whereUserId($value)
 */
	class Executor extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ExecutorServiceType
 *
 * @property int $id
 * @property int $executor_id
 * @property int $order_category_id
 * @method static \Illuminate\Database\Eloquent\Builder|ExecutorServiceType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ExecutorServiceType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ExecutorServiceType query()
 * @method static \Illuminate\Database\Eloquent\Builder|ExecutorServiceType whereExecutorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExecutorServiceType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExecutorServiceType whereOrderCategoryId($value)
 */
	class ExecutorServiceType extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Favorite
 *
 * @property int $id
 * @property int $user_id
 * @property int $executor_id
 * @property int $order_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Executor|null $executor
 * @property-read \App\Models\Order|null $order
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite query()
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite whereExecutorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Favorite whereUserId($value)
 */
	class Favorite extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Invoice
 *
 * @property int $id
 * @property string $invoiceable_type
 * @property int $invoiceable_id
 * @property int $subscription_id
 * @property string $status
 * @property mixed|null $meta
 * @property string|null $expired_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $invoiceable
 * @property-read \App\Models\Subscription|null $subscription
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereInvoiceableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereInvoiceableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Invoice whereUpdatedAt($value)
 */
	class Invoice extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\MediaFiles
 *
 * @property int $id
 * @property string $storage_link
 * @property int $active
 * @property string $mediable_type
 * @property int $mediable_id
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $mediable
 * @method static \Illuminate\Database\Eloquent\Builder|MediaFiles newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MediaFiles newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MediaFiles query()
 * @method static \Illuminate\Database\Eloquent\Builder|MediaFiles whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MediaFiles whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MediaFiles whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MediaFiles whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MediaFiles whereMediableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MediaFiles whereMediableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MediaFiles whereStorageLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MediaFiles whereUpdatedAt($value)
 */
	class MediaFiles extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Order
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property float|null $price_max
 * @property float|null $price_recommended
 * @property int $category_id
 * @property int $city_id
 * @property int|null $executor_id
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OrderCategory|null $category
 * @property-read \App\Models\Chat|null $chatable
 * @property-read \App\Models\City|null $city
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read int|null $comments_count
 * @property-read \App\Models\Executor|null $executor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MediaFiles> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderOffer> $offers
 * @property-read int|null $offers_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereExecutorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePriceMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePriceRecommended($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUserId($value)
 */
	class Order extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\OrderCategory
 *
 * @property int $id
 * @property string $title
 * @property int $parent_id
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderCategory> $child
 * @property-read int|null $child_count
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereUpdatedAt($value)
 */
	class OrderCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\OrderOffer
 *
 * @property int $id
 * @property int $order_id
 * @property int $user_id
 * @property int $city_id
 * @property string $price
 * @property string $date
 * @property string|null $comment
 * @property string|null $expired_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\City|null $city
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|OrderOffer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderOffer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderOffer query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderOffer whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderOffer whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderOffer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderOffer whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderOffer whereExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderOffer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderOffer whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderOffer wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderOffer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderOffer whereUserId($value)
 */
	class OrderOffer extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\PhoneConfirmation
 *
 * @property int $id
 * @property int $user_id
 * @property string $phone
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneConfirmation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneConfirmation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneConfirmation query()
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneConfirmation whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneConfirmation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneConfirmation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneConfirmation wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneConfirmation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PhoneConfirmation whereUserId($value)
 */
	class PhoneConfirmation extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ProductCategory
 *
 * @property int $id
 * @property string $title
 * @property int $parent_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductCategory> $child
 * @property-read int|null $child_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductCategory withoutTrashed()
 */
	class ProductCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Rating
 *
 * @property int $id
 * @property int $user_id
 * @property float $rate
 * @property string $ratingable_type
 * @property int $ratingable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $ratingable
 * @method static \Illuminate\Database\Eloquent\Builder|Rating newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rating newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Rating query()
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereRatingableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereRatingableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Rating whereUserId($value)
 */
	class Rating extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ServiceType
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceType onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceType query()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceType withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceType withoutTrashed()
 */
	class ServiceType extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Store
 *
 * @property int $id
 * @property int $user_id
 * @property int $type_id
 * @property string $name
 * @property string $bin
 * @property int $city_id
 * @property string $lat
 * @property string $lon
 * @property string $full_address
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rating> $rating
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\City|null $city
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StoreContacts> $contacts
 * @property-read int|null $contacts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MediaFiles> $media
 * @property-read int|null $media_count
 * @property-read int|null $rating_count
 * @property-read \App\Models\CompanyType|null $type
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Store newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Store newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Store query()
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereBin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereFullAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Store whereUserId($value)
 */
	class Store extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\StoreContacts
 *
 * @property int $id
 * @property int $store_id
 * @property string $type
 * @property string|null $contact_name
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|StoreContacts newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreContacts newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreContacts query()
 * @method static \Illuminate\Database\Eloquent\Builder|StoreContacts whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreContacts whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreContacts whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreContacts whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreContacts whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreContacts whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StoreContacts whereValue($value)
 */
	class StoreContacts extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Subscription
 *
 * @property int $id
 * @property string $type
 * @property int $validity
 * @property string $price
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription query()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereValidity($value)
 */
	class Subscription extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property int $city_id
 * @property string|null $photo_url
 * @property string $password
 * @property int $is_phone_confirmed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $push_notifications
 * @property string|null $device_token
 * @property-read \App\Models\City|null $city
 * @property-read \App\Models\Executor|null $executor
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Store|null $store
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeviceToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsPhoneConfirmed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhotoUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePushNotifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\UserSubscription
 *
 * @property int $id
 * @property int $user_id
 * @property int $subscription_id
 * @property string $expired_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|UserSubscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserSubscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserSubscription query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserSubscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSubscription whereExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSubscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSubscription whereSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSubscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserSubscription whereUserId($value)
 */
	class UserSubscription extends \Eloquent {}
}

