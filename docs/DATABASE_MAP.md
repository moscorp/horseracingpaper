# Database map — `fengrmkw_moosay`
Source: `fengrmkw_moosay.sql` — 92 tables (structure dump).

## marksix (11)
| Table | Cols | Key columns (sample) |
|---|---:|---|
| `00m6` | 11 | `id`, `year`, `nos`, `no1`, `no2`, `no3`, `no4`, `no5` |
| `00m6estpos` | 11 | `id`, `year`, `nos`, `no1`, `no2`, `no3`, `no4`, `no5` |
| `hkjc_marksix_draws` | 9 | `id`, `year`, `draw_no`, `status`, `draw_date`, `drawn_order_nos_json`, `drawn_order_nos_count`, `created_at` |
| `hkms_gann` | 4 | `id`, `laterest_drawdate`, `jpgfilename`, `rectime` |
| `m6_all_data_accuracy` | 13 | `id`, `method`, `correct`, `total`, `accuracy`, `no1_accuracy`, `no2_accuracy`, `no3_accuracy` |
| `m6_backtest_accuracy` | 9 | `id`, `method`, `training_rows`, `target_column`, `mae`, `hit_rate`, `near_hit_rate`, `tested_draws` |
| `m6_blog_posts` | 9 | `id`, `draw_id`, `draw_year`, `draw_nos`, `post_type`, `wp_post_id`, `title`, `prediction_date` |
| `m6_current_year_accuracy` | 13 | `id`, `method`, `correct`, `total`, `accuracy`, `no1_accuracy`, `no2_accuracy`, `no3_accuracy` |
| `m6_draw_analysis` | 7 | `id`, `draw_id`, `method`, `correct_count`, `accuracy`, `rank_position`, `created_at` |
| `m6_position_predictions` | 14 | `id`, `target_draw_id`, `draw_year`, `draw_nos`, `method`, `training_rows`, `pred_no1`, `pred_no2` |
| `m6_simulated_accuracy` | 13 | `id`, `method`, `total_correct`, `total_predictions`, `overall_accuracy`, `no1_accuracy`, `no2_accuracy`, `no3_accuracy` |

## racing_core (12)
| Table | Cols | Key columns (sample) |
|---|---:|---|
| `hkracing_active_meetings` | 2 | `meeting_id`, `activated_at` |
| `hkracing_cache` | 4 | `cache_key`, `response_data`, `created_at`, `expires_at` |
| `hkracing_history_queue` | 9 | `id`, `horse_code`, `horse_name_ch`, `status`, `priority`, `retry_count`, `last_attempt`, `created_at` |
| `hkracing_horse_fetch_log` | 7 | `id`, `horse_code`, `status`, `error_message`, `performances_count`, `duration_ms`, `created_at` |
| `hkracing_horse_performances` | 23 | `id`, `horse_code`, `season`, `race_no`, `finishing_position`, `race_date`, `venue_code`, `course` |
| `hkracing_horses` | 29 | `horse_code`, `horse_id`, `name_en`, `name_ch`, `age`, `sex`, `colour`, `import_type` |
| `hkracing_incremental_queue` | 9 | `id`, `horse_code`, `horse_name_ch`, `reason`, `status`, `retry_count`, `last_attempt`, `created_at` |
| `hkracing_meetings` | 12 | `id`, `venue_code`, `date`, `status`, `total_number_of_race`, `current_number_of_race`, `date_of_week`, `meeting_type` |
| `hkracing_pending_horses` | 10 | `id`, `horse_code`, `horse_name_ch`, `status`, `priority`, `sync_reason`, `retry_count`, `last_attempt` |
| `hkracing_races` | 24 | `id`, `meeting_id`, `race_no`, `status`, `race_name_en`, `race_name_ch`, `post_time`, `country_en` |
| `hkracing_runners` | 26 | `id`, `race_id`, `horse_code`, `horse_id`, `runner_no`, `standby_no`, `status`, `name_ch` |
| `hkracing_screenshot_tasks` | 15 | `id`, `race_date`, `venue_code`, `race_no`, `share_url`, `image_path`, `image_size`, `status` |

## odds (9)
| Table | Cols | Key columns (sample) |
|---|---:|---|
| `historyodds` | 7 | `id`, `venue`, `raceno`, `horseid`, `historyodds`, `rank`, `rectime` |
| `historyodds_pattern` | 3 | `id`, `pattern`, `pla` |
| `hkracing_odds_cwin_selections` | 10 | `id`, `race_id`, `odds_type`, `pool_type`, `composite`, `name_ch`, `name_en`, `starters` |
| `hkracing_odds_data` | 9 | `id`, `race_date`, `venue_code`, `race_no`, `odds_type`, `data_snapshot`, `horse_odds`, `captured_at` |
| `hkracing_odds_horse_details` | 24 | `id`, `odds_data_id`, `race_date`, `venue_code`, `race_no`, `horse_code`, `horse_name_ch`, `horse_name_en` |
| `hkracing_odds_nodes` | 10 | `id`, `race_id`, `odds_type`, `pool_type`, `comb_string`, `odds_value`, `hot_favourite`, `odds_drop_value` |
| `hkracing_odds_pools` | 14 | `id`, `race_id`, `odds_type`, `pool_type`, `status`, `sell_status`, `last_update_time`, `name_en` |
| `oddsDrop` | 11 | `id`, `date`, `venue`, `horsecode`, `horsename`, `pre_prop`, `curr_prop`, `pre_win` |
| `oddshistory` | 7 | `id`, `type`, `racingdate`, `venue`, `mtgTotalRace`, `odds`, `rectime` |

## barrier (3)
| Table | Cols | Key columns (sample) |
|---|---:|---|
| `barriercomment` | 3 | `id`, `comment`, `rank` |
| `barrierday` | 2 | `id`, `barrierday` |
| `barrierresult` | 17 | `id`, `barrierday`, `Going`, `Horse`, `Jockey`, `Trainer`, `Draw`, `Gear` |

## scores (1)
| Table | Cols | Key columns (sample) |
|---|---:|---|
| `hkracing_v2_score` | 33 | `id`, `race_id`, `horse_code`, `runner_no`, `score_win_rate`, `score_recent_form`, `score_draw_history`, `score_trainer_jockey` |

## blog (3)
| Table | Cols | Key columns (sample) |
|---|---:|---|
| `hkracing_blog_posts` | 6 | `id`, `race_date`, `venue_code`, `wp_post_id`, `created_at`, `score_version` |
| `hkracing_poster_log` | 11 | `id`, `race_date`, `venue_code`, `race_no`, `image_path`, `image_filename`, `image_size`, `facebook_post_id` |
| `hkracing_review_posts` | 5 | `id`, `race_date`, `venue_code`, `wp_post_id`, `created_at` |

## funds (3)
| Table | Cols | Key columns (sample) |
|---|---:|---|
| `funds` | 7 | `id`, `UNIT_PRICE_DATE`, `FUND_CODE`, `FUND_NAME`, `FUND_CURRENCY`, `PRICE`, `rectime` |
| `funds_hsi` | 21 | `id`, `tradeday`, `currency`, `symbol`, `exchangeName`, `fullExchangeName`, `instrumentType`, `firstTradeDate` |
| `funds_hsi_indicators` | 8 | `id`, `tradeday`, `high`, `low`, `close`, `volume`, `open`, `adjclose` |

## horseinfo (16)
| Table | Cols | Key columns (sample) |
|---|---:|---|
| `horseinfo` | 22 | `id`, `horseid`, `horsename`, `RaceIndex`, `Pla`, `Date`, `RC_Track_Course`, `Dist` |
| `horseinfo240320` | 21 | `id`, `horseid`, `horsename`, `RaceIndex`, `Pla`, `Date`, `RC_Track_Course`, `Dist` |
| `horseinfo_hints` | 12 | `id`, `racingdate`, `venue`, `mtgTotalRace`, `raceno`, `horseno`, `ai2pos`, `trackpla` |
| `horseinfo_old` | 20 | `id`, `horseid`, `RaceIndex`, `Pla`, `Date`, `RC_Track_Course`, `Dist`, `G` |
| `horseinfo_old2` | 21 | `id`, `horseid`, `horsename`, `RaceIndex`, `Pla`, `Date`, `RC_Track_Course`, `Dist` |
| `horseinfo_summary` | 15 | `id`, `Trainer`, `Jockey`, `day_racecnt`, `day_ispla`, `day_iswin`, `night_racecnt`, `night_ispla` |
| `hrp_pick` | 10 | `id`, `racingdate`, `venue`, `raceno`, `horseno`, `horsename`, `signalx`, `pick` |
| `hrp_pick2` | 9 | `id`, `racingdate`, `venue`, `raceno`, `pick1`, `pick2`, `pick3`, `pick4` |
| `hrp_propanalysis` | 14 | `id`, `venue`, `proptop`, `prop`, `samecnt`, `isbigger`, `issmaller`, `pos` |
| `hrp_prophist` | 17 | `id`, `racingdate`, `venue`, `raceno`, `horseno`, `proptime`, `prop`, `prop_curr` |
| `hrp_prophist2` | 25 | `id`, `racingdate`, `venue`, `raceno`, `horseno`, `horsecode`, `postTime`, `pre_WIN` |
| `RaceCard` | 31 | `id`, `racingdate`, `venue`, `raceno`, `HorseNo`, `Last6Runs`, `Colour`, `horsename` |
| `RaceCard_sub` | 31 | `id`, `racingdate`, `venue`, `raceno`, `HorseNo`, `Last6Runs`, `Colour`, `horsename` |
| `racingrecord` | 51 | `id`, `racingdate`, `venueLong`, `venue`, `raceno`, `Distance`, `Track`, `Horseno` |
| `racingrecord_summary` | 51 | `id`, `racingdate`, `venueLong`, `venue`, `raceno`, `Distance`, `Track`, `Horseno` |
| `rsdata` | 19 | `id`, `top5Jockey`, `top5Trainer`, `mtgDate`, `mtgVenue`, `dayShort`, `venueShort`, `dayLong` |

## prop (6)
| Table | Cols | Key columns (sample) |
|---|---:|---|
| `prop_manual_rule` | 13 | `id`, `code`, `name`, `venue_scope`, `priority`, `enabled`, `mark_weight`, `condition_json` |
| `prop_manual_rule_evidence` | 7 | `id`, `rule_id`, `racingdate`, `venue`, `raceno`, `prop_string`, `result_json` |
| `prop_manual_rule_stat` | 12 | `rule_id`, `venue_scope`, `date_from`, `date_to`, `fp_max`, `races_matched`, `picks_total`, `picks_placed` |
| `prop_race_fingerprint` | 12 | `id`, `racingdate`, `venue`, `venue_bucket`, `raceno`, `distance`, `go_ch`, `n_horses` |
| `racepropresult` | 13 | `id`, `racingdate`, `venue`, `go_ch`, `horsecode`, `horsename`, `raceno`, `Distance` |
| `racepropresult_bak` | 13 | `id`, `racingdate`, `venue`, `go_ch`, `horsecode`, `horsename`, `raceno`, `Distance` |

## messaging (20)
| Table | Cols | Key columns (sample) |
|---|---:|---|
| `message_campaigns` | 19 | `id`, `campaign_name`, `campaign_description`, `group_id`, `message_type`, `message_content`, `media_url`, `caption` |
| `message_group_users` | 5 | `id`, `group_id`, `user_id`, `added_at`, `added_by` |
| `message_groups` | 8 | `id`, `group_name`, `group_description`, `group_type`, `status`, `created_by`, `created_at`, `updated_at` |
| `message_logs` | 15 | `id`, `user_id`, `queue_id`, `message_id`, `phone_number`, `chat_id`, `message_type`, `content_preview` |
| `message_queue` | 17 | `id`, `user_id`, `session_id`, `message_type`, `message_content`, `media_url`, `caption`, `priority` |
| `message_target_user` | 17 | `id`, `user_id`, `phone_number`, `name`, `country_code`, `status`, `opt_in`, `opt_in_date` |
| `wa_campaign_messages` | 14 | `id`, `campaign_id`, `contact_id`, `status`, `whatsapp_message_id`, `sent_at`, `delivered_at`, `read_at` |
| `wa_campaigns` | 22 | `id`, `user_id`, `uuid`, `name`, `wa_session_id`, `message_type`, `message_body`, `media_url` |
| `wa_contacts` | 14 | `id`, `user_id`, `uuid`, `name`, `phone`, `email`, `tags`, `consent_status` |
| `wa_sessions` | 14 | `id`, `user_id`, `uuid`, `name`, `phone_number`, `session_id`, `status`, `qr_code_path` |
| `wapi_campaign_messages` | 14 | `id`, `campaign_id`, `contact_id`, `status`, `whatsapp_message_id`, `sent_at`, `delivered_at`, `read_at` |
| `wapi_campaigns` | 22 | `id`, `user_id`, `uuid`, `name`, `wa_session_id`, `message_type`, `message_body`, `media_url` |
| `wapi_contacts` | 14 | `id`, `user_id`, `uuid`, `name`, `phone`, `email`, `tags`, `consent_status` |
| `wapi_message_logs` | 14 | `id`, `user_id`, `campaign_id`, `campaign_message_id`, `wa_session_id`, `direction`, `from_number`, `to_number` |
| `wapi_migrations` | 3 | `id`, `migration`, `batch` |
| `wapi_personal_access_tokens` | 10 | `id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at` |
| `wapi_subscriptions` | 12 | `id`, `user_id`, `stripe_subscription_id`, `stripe_price_id`, `status`, `current_period_start`, `current_period_end`, `cancel_at_period_end` |
| `wapi_users` | 21 | `id`, `uuid`, `email`, `email_verified_at`, `password`, `name`, `phone`, `timezone` |
| `wapi_wa_sessions` | 14 | `id`, `user_id`, `uuid`, `name`, `phone_number`, `session_id`, `status`, `qr_code_path` |
| `whatsapp_logs` | 9 | `id`, `log_type`, `action`, `user_id`, `phone_number`, `message`, `details`, `ip_address` |

## other (8)
| Table | Cols | Key columns (sample) |
|---|---:|---|
| `logs` | 3 | `id`, `type`, `time` |
| `logs.251129` | 3 | `id`, `type`, `time` |
| `migrations` | 3 | `id`, `migration`, `batch` |
| `mmkb` | 4 | `id`, `symptoms`, `solution`, `tag` |
| `personal_access_tokens` | 10 | `id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at` |
| `racedata_stheadline` | 12 | `id`, `race_date`, `startTime`, `venue`, `race_no`, `horse_no`, `win_value`, `win_investment` |
| `raceresult_pattern` | 5 | `id`, `racingday`, `raceno`, `wpratio_pattern`, `result_orderbyprew` |
| `users` | 20 | `id`, `uuid`, `email`, `email_verified_at`, `password`, `name`, `phone`, `timezone` |
